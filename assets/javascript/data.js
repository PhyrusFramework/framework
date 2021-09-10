class DataBindNode {

    /**
     * jQuery elements.
     * 
     */
    elements = {
        this: null,
        parent: null
    }

    /**
     * Nodes
     * 
     */
    nodes = {
        parent: null,
        before: null,
        after: null,
        children: []
    }

    /**
     * This node has operations? (class, style, if, click...)
     */
    used = false;

    /**
     * This node has used children?
     */
    needed = false;

    /**
     * Is this element currently in the DOM?
     */
    inDOM = true;

    /**
     * :class property
     */
    class_template;

    /**
     * :style property
     */
    style_template;

    /**
     * :model property
     */
    model_template;

    /**
     * Element alias.
     */
    alias;

    /**
     * :if property
     */
    condition_template;

    /**
     * Events: click, mouseover, focus...
     */
    events = {}

    /**
     * :component property
     */
    component_name;

    /**
     * HTML of this element. Used only for lists.
     */
    template = '';

    /**
     * :for property
     */
    list_template;

    /**
     * List of nodes generated in a :for list.
     */
    list_nodes = [];

    /**
     * Data to be loaded when working with this node.
     */
    state = {};

    /**
     * Previous window values before loading state.
     */
    _savedState = {}

    /**
     * Any attribute starting with :
     */
     genericAttributes = {}

    constructor(options) {

        let e = $(options.element);

        this.elements = {
            this: e,
            parent: e.parent()
        }

        this.nodes = {
            parent: options.parent,
            children: [],
            before: null
        }

        // If has attribute static, avoid data-binding
        if (e.attr('static') !== undefined) {
            return;
        }

        // Get Node data
        if (options.data) {
            Object.keys(options.data).forEach(k => {
                this.state[k] = options.data[k];
            });
        }

        // Check all tags;

        let tag = e.prop('tagName');

        if (e.attr(':component')) {
            this.component_name = e.attr(':component');
            e.removeAttr(':component');
            this.used = true;
            Component.init(this);
            return;
        }

        if (e.attr(':for')) {
            this.list_template = e.attr(':for');
            e.removeAttr(':for');
            this.template = e.get(0).outerHTML;
            this.used = true;
            //e.remove();
            e.hide();
            return;
        }

        let attrs = {
            class: value => {
                this.class_template = value;
            },
            style: value => {
                this.style_template = value;
            },
            model: value => {
                this.model_template = value;

                if (tag) {

                    let type = e.attr('type');
                    let isCheckbox = type == 'checkbox';

                    if (!isCheckbox && [
                        'input',
                        'textarea'
                    ].includes(tag.toLowerCase())) {
                        e.keyup(function() {
                            let val = $(this).val();
                            eval(value + ' = val');
                            View.update();
                        });
                    }

                    if (isCheckbox || [
                        'select'
                    ].includes(tag.toLowerCase())) {
                        e.change(function() {

                            if (isCheckbox) {
                                let val = $(this).is(':checked');
                                eval(value + ' = val')
                            } else {
                                let op = $(this).find('option:selected').val();
                                eval(value + ' = op')
                            }

                            View.update();
                        });
                    }
                }
            },
            if: value => {
                this.condition_template = value;
            },
            alias: value => {
                window[value] = this.elements.this;
                this.alias = value;
            }
        }

        Object.keys(attrs).forEach(k => {
            if (e.hasAttr(':' + k)) {
                let value = e.attr(':' + k);
                attrs[k](value);
                this.used = true;
                e.removeAttr(':'+k);
            }
        });

        let events = [
            'click',
            'change',
            'keyup',
            'mouseover',
            'mouseenter',
            'mouseleave',
            'update',
            'pressEnter',
            'focus',
            'blur'
        ];

        for(let ev of events) {
            if (e.hasAttr(':' + ev)) {
                let value = e.attr(':' + ev);
                this.used = true;
                this.events[ev] = value;
                e.removeAttr(':'+ev);
            }
        }

        this.reloadEvents();

        attrs = e.getAttributes();
        Object.keys(attrs).forEach(attr => {
            if (attr[0] != ':') return;
            this.used = true;
            this.genericAttributes[attr.substr(1)] = attrs[attr];
            e.removeAttr(attr);
        });

        if (!options.loadChildren) return;

        this.loadChildren();
    }

    /**
     * Load Node children elements.
     */
    loadChildren() {
        let children = this.elements.this.children();

        let beforeNode = null;

        for(let i = 0; i < children.length; ++i) {

            let child = children.get(i);
            if (!child) continue;
            let tag = $(child).prop('tagName');
            if (!tag) continue;

            if (['script', 'style'].includes(tag.toLowerCase())) continue;

            let childNode = new DataBindNode({
                element: child,
                data: null,
                parent: this,
                loadChildren: true
            });

            childNode.nodes.before = beforeNode;
            beforeNode = childNode;

            if (childNode.used || childNode.needed) {
                this.needed = true;
                this.nodes.children.push(childNode);
            }
        }
    }

    /**
     * Get previous element when reinserting this node into the DOM.
     */
    getAnchorElement() {

        if (this.inDOM) {
            return this.elements.this;
        }

        if (!this.nodes.before) {
            return null;
        }

        return this.nodes.before.getAnchorElement();

    }

    /**
     * [Managed automatically]
     * Re-add events for the jQuery element.
     * This is needed when the element is removed and re-added.
     */
    reloadEvents() {

        let e = this.elements.this;
        let ref = this;

        Object.keys(this.events).forEach(ev => {

            if (['update', 'pressEnter'].includes(ev)) {
                return;
            }

            let value = this.events[ev];
            e.on(ev, function() {
                ref.setupState();
                eval(value);
                View.update();
                ref.recoverPreviousState();
            });
        });

        if (this.events.pressEnter) {
            let value = this.events.pressEnter;
            e.keyup(function($ev) {
                if($ev.keyCode == 13)
                {
                    ref.setupState();
                    eval(value);
                    ref.recoverPreviousState();
                }
            });
        }

        if (this.events.update) {
            let value = this.events.update;
            e.change(function() {
                ref.setupState();
                eval(value);
                ref.recoverPreviousState();
            });
            e.keyup(function() {
                ref.setupState();
                eval(value);
                ref.recoverPreviousState();
            });
        }

        for(let child of this.nodes.children) {
            child.reloadEvents();
        }
    }

    /**
     * Return Node to the page after the element
     * had been removed.
     */
    returnToView() {
        let e = this.elements.this;
        if (!this.nodes.parent) return;

        View.disableNextAddition = true;

        if (this.alias) {
            window[alias] = e;
        }

        if (!this.nodes.before) {
            this.elements.parent.prepend(e);
        } else {
            let anchor = this.nodes.before.getAnchorElement();
            if (anchor) {
                anchor.after(e);
            } else
                this.elements.parent.prepend(e);
        }

        this.reloadEvents();
        this.inDOM = true;
    }

    /**
     * Update :for list elements.
     */
    _updateList() {

        // 'item of list'   item -> itemName  list -> listname
        let listname = this.list_template.split(/ /g);
        let itemName = listname[0];
        listname = listname[listname.length - 1];
        
        let __list__ = [];
        eval('__list__ = ' + listname);
        if (!__list__) return;

        let i = 0;
        for(i = 0; i < __list__.length; ++i) {

            let bondData = {}
            bondData[itemName] = __list__[i];

            if (this.list_nodes.length < i+1) {
                // Add new
                let el = $(this.template);

                View.disableNextAddition = true;

                if (this.list_nodes.length > 0) {
                    this.list_nodes[this.list_nodes.length - 1].elements.this.after(el);
                } else{
                    this.elements.this.after(el);
                }

                this.list_nodes.push(new DataBindNode({
                    element: el.get(0), 
                    data: bondData,
                    parent: this.nodes.parent,
                    loadChildren: true
                }));

            } else {
                this.list_nodes[i].state = bondData;
            }

        }

        this.elements.this.remove();

        if (this.list_nodes.length > __list__.length) {
            while(this.list_nodes.length > __list__.length) {
                this.list_nodes[this.list_nodes.length - 1].elements.this.remove();
                this.list_nodes.splice(this.list_nodes.length - 1, 1);
            }
        }


        for(let i = 0; i < __list__.length; ++i) {
            let node = this.list_nodes[i];
            node.update(true);
        }

    }

    /**
     * Obtain the value from the :model
     */
    _modelValue() {
        let val = null;
        try {
            eval('val = ' + this.model_template);
        } catch(exc) {
            val = '';
        }

        return val;
    }

    setupState() {

        this._savedState = {}

        if (this.state) {
            Object.keys(this.state).forEach(k => {
                this._savedState[k] = window[k];
                window[k] = this.state[k];
            });
        }
    }

    recoverPreviousState() {
        Object.keys(this._savedState).forEach(k => {
            this.state[k] = window[k];
            window[k] = this._savedState[k];
        });
        this._savedState = {}
    }

    update(propagate = true) {

        this.setupState();

        if (this.used) {

            let e = this.elements.this;

            // FOR
            if (this.list_template) {
                this._updateList();
                this.recoverPreviousState();
                return;
            }

            // IF
            if (this.condition_template) {
                let b = true;
                eval('b = ' + this.condition_template);
                if (!b && this.inDOM) {

                    if (this.alias) {
                        window[alias] = null;
                    }

                    e.remove();
                    this.inDOM = false;
                    this.recoverPreviousState();
                    return;
                } else if (b && !this.inDOM) {
                    this.returnToView();
                }
            }

            // MODEL
            if (this.model_template) {

                let val = this._modelValue();

                let tag = e.prop('tagName');
                if (tag && [
                    'input',
                    'textarea',
                    'select'
                ].includes(tag.toLowerCase())) {

                    let type = e.attr('type');
                    let isCheckbox = type == 'checkbox';

                    if (isCheckbox) {
                        e.prop('checked', val);
                    } else {
                        e.val(val);
                    }

                } else {

                    if (val !== null && val !== undefined) {
                        e.html(val);
                    } else {
                        e.html('');
                    }
                }

            }

            // CLASS
            if (this.class_template) {
                let obj = null;
                eval('obj = ' + this.class_template);
                if (obj) {
                    Object.keys(obj).forEach(k => {
                        if (obj[k]) {
                            e.addClass(k);
                        }
                    });
                }
            }

            // STYLE
            if (this.style_template) {
                let obj = null;
                eval('obj = ' + this.style_template);
                if (obj) {
                    e.styles(obj);
                }
            }

            // GENERIC ATTRIBUTES
            Object.keys(this.genericAttributes).forEach(attr => {
                e.attr(attr, eval(this.genericAttributes[attr]));
            });

        }

        if (!propagate) {
            this.recoverPreviousState();
            return;
        }

        for(let child of this.nodes.children) {
            child.update(propagate);
        }

        this.recoverPreviousState();

    }

    addChild(node) {
        if (!node.needed && !node.used) {
            return;
        }
        this.elements.children.push(node);
    }

    removeChild(node) {
        let pos = this.nodes.children.indexOf(node);
        if (pos >= 0)
        this.nodes.children.splice( pos, 1 );
    }

    remove() {
        this.elements.this.remove();
        this.used = false;
        this.needed = false;

        this.nodes.parent.removeChild(this);
    }

    replace(e, data = {}) {
        View.disableNextAddition = true;
        this.elements.this.replace(e);
        this.remove();

        let d = {}
        Object.keys(this.state).forEach(k => {
            d[k] = this.state[k];
        });
        Object.keys(data).forEach(k => {
            d[k] = data[k];
        });

        let newNode = new DataBindNode({
            element: e.get(0),
            data: d,
            parent: this.nodes.parent,
            loadChildren: true
        });

        if (newNode.used || newNode.needed) {
            this.nodes.parent.needed = true;
            this.nodes.parent.nodes.children.push(newNode);
        }
        return newNode;
    }
}

class DataBinding {

    disableNextAddition = false;
    root;

    constructor() { }

    setRoot(element) {
        this.root = new DataBindNode({
            element: $(element).get(0),
            loadChildren: false
        });

        this.root.loadChildren();
    }

    update() {
        this.root.update();
    }

    recalculate() {
        this.setRoot('body');
    }

    add(element, data = {}) {
        if (this.disableNextAddition) {
            this.disableNextAddition = false;
            return;
        }
        if (!this.root) return;
        this.root.nodes.children.push(new DataBindNode({
            element: element,
            loadChildren: true,
            data: data
        }));
    }

}

var View = new DataBinding();

$(document).ready(() => {
    View.recalculate();
    View.update();
});

// JQuery
onInsertElement(e => {
    if (!e || !e['find']) return;
    View.add(e.get(0));
});

function delay(millis) {
    return new Promise((resolve, reject) => {

        setTimeout(() => {
            resolve(true);
            setTimeout(() => {
                View.update();
            }, 5);
        }, millis);

    });
}