class DataBinding {

    fors = [];

    set(path, value, target = null, refresh = true) {

        let varname = replacer(path, "[", "]", content => {
            return "." + content;
        });

        let tar = target ? target : window;

        let parts = varname.split(".");
        if (parts.length == 0) return;
        if (parts.length == 1) {
            tar[parts[0]] = value;
            if (refresh)
                this.Refresh();
            return;
        }

        Data.__setProp(tar, parts, value);

        if (refresh)
        this.Refresh();
    }

    __setProp(target, path, value) {
        if (path.length == 0) return {};

        let key = path[0];

        if (path.length == 1) {
            target[key] = value;
            return target;
        }
        else {
            if (!target[key]) target[key] = {}
            let obj = target[key];
            let aux = [];
            for(let i = 1; i<path.length; ++i) {
                aux.push(path[i]);
            }

            target[key] = Data.__setProp(obj, aux, value);
            return target;
        }
    }

    checkPropExists(path, target = null) {
        let tar = target ? target : window;
        if (!path) return null;

        let varname = replacer(path, "[", "]", content => {
            return "." + content;
        });

        let parts = varname.split(".");
        if (parts.length == 0) return null;
        if (parts.length == 1) {
            return window[parts[0]] !== null && window[parts[0]] !== undefined;
        }

        let obj = tar;
        for(let p of parts) {

            if (!Object.keys(obj).includes(p)) {
                return null;
            }
            obj = obj[p];
        }
        return obj;
    }

    getProp(path, target = null, defaultValue = "") {

        let tar = target ? target : window;

        let varname = replacer(path, "[", "]", content => {
            return "." + content;
        });

        let parts = varname.split(".");
        let obj = tar;

        for(let i = 0; i<parts.length; ++i) {
            let p = parts[i];

            if (!Object.keys(obj).includes(p)) {
                if (i < parts.length - 1)
                    obj[p] = {};
                else
                    obj[p] = defaultValue;
            }
            obj = obj[p];
        }
        return obj;
    }

    constructor() {
        $(document).ready(() => {
            this.analyze($("body"));
            this.Refresh();
        });
    }

    Run(cb) {
        cb();
        this.Refresh();
    }

    analyze(body) {

        // WRITE EVENTS
        this.analyzeVars(body);

        //// FORS

        let elements = $("*[forList]");
        elements.each(function() {

            let prop = $(this).attr("forList");
            $(this).removeAttr("forList");

            //let list = Data.getProp(prop, window, []);

            let forObject = {
                container: $(this),
                template: $(this).html(),
                listname: prop,
                views: {},
                count: 0
            };
            $(this).empty();
            Data.fors.push(forObject);
        });
    }

    toJson(t) {
        let obj = {};

        let values = t.split(",");
        for(let val of values) {

            let ind = val.indexOf(":");

            let key = val.substring(0, ind).replace(/ /g, "");
            let condition = val.substr(ind + 1);

            obj[key] = condition;

        }

        return obj;
    }

    setViewValue(e, value) {

        if (value === null) return;

        let tag = e.prop("tagName").toLowerCase();

        if (tag == "input" || tag == "textarea")
        {
            if (e.attr("type") == "checkbox") {
                e.prop("checked", value ? true : false);
            } else {
                e.val(value);
            }

        }
        else if (tag == "select")
        {
            e.val(value);
        } 
        else if (["img", "audio", "video", "source"].includes(tag)) {
            e.attr("src", value);
        }
        else {

            e.text(value);
        }
    }

    analyzeVars(e = null, target = null) {

        let body = e ? e : $("body");

        let ref = this;

        let elements = body.find("*[var]").andSelf();
        elements.each(function(){
    
            let tag = $(this).prop("tagName").toLowerCase();
            let varname = $(this).attr("var");
            if (!varname) return;
    
            if (tag == "input" || tag == "textarea")
            {
                let type = $(this).attr("type");
                if (type == "checkbox")
                {
                    $(this).change(function(){
                        ref.set(varname, $(this).is(":checked"), target);
                    });
                }
                else if (type == "color") {
                    $(this).change(function(){
                        ref.set(varname, $(this).val(), target);
                    });
                }
                else{

                    let update = function(){

                        let val = $(this).val();
                        let type = $(this).attr("type");
                        if (type && ["number", "numeric"].includes(type))
                            val = parseFloat(val);

                        ref.set(varname, val, target);
        
                    }
                    $(this).keyup(update);
                    $(this).change(update);
                }
    
            }
            else if (tag == "select")
            {
                $(this).change(function(){
                    let val = $(this).find("option:selected").val();
                    ref.set(varname, val, target);
                });
            }
    
        });

        let code = "";
        if (target) {
            Object.keys(target).forEach(k => {
                code += "let " + k + " = target['" + k + "']; ";
            });
        }

        ///// EVENTS
        let events = ["click", "mouseenter", "mouseleave", "mousemove", "change", "keyup"];

        for(let i = 0; i<events.length; ++i) {

            elements = body.find("*[" + events[i] + "]").andSelf();
            elements.each(function() {
                $(this)[events[i]](function($e) {
                    let ev = $(this).attr(events[i]);
                    if(!ev) return;

                    let $this = $(this);

                    eval(code + ev);
                    Data.Refresh();
                });
            });
        }
        elements = body.find("*[update]").andSelf();
        elements.each(function($e) {
            $(this).change(function($e) {
                let ev = $(this).attr("update");
                if (!ev) return;
                eval(code + ev);
                Data.Refresh();
            });

            $(this).keyup(function($e) {
                let ev = $(this).attr("update");
                if (!ev) return;
                eval(code + ev);
                Data.Refresh();
            });
        });
    
        elements = body.find("*[pressEnter]").andSelf();
        elements.each(function() {
            $(this).keyup(function($e) {
                if($e.keyCode == 13)
                {
                    let ev = $(this).attr("pressEnter");
                    if (!ev) return;
                    eval(code + ev);
                    Data.Refresh();
                }
            });
        });

        // alias
        elements = body.find("*[alias]").andSelf();
        elements.each(function() {
            let alias = $(this).attr("alias");
            if (!alias) return;
            window[alias] = $(this);
        });

        // Load
        elements = body.find("*[load]").andSelf();
        elements.each(function() {
            let onload = $(this).attr("load");
            if (!onload) return;
            $(this).removeAttr("load");
            eval(onload);
        });

    }

    Refresh() {

        // Fors
        for(let f of this.fors) {

            // Check if list exists
            if (!f || !f.listname || !Data.checkPropExists(f.listname))
                continue;
            //

            let list = Data.getProp(f.listname);

            // If not items added or removed, nothing to do here.
            if (f.count == list.length) {
                continue;
            }

            for(let $index = 0; $index < list.length; ++$index) {
                if (f.views[$index]) {
                    f.views[$index].inList = false;
                }
            }

            for(let $index = 0; $index < list.length; ++$index) {
                let item = list[$index];

                let generateHTML = () => {
                    return replacer(f.template, '[[', ']]', content => {

                        let str = content.replace(/\$item/g, f.listname +"["+$index+"]");
                        str = str.replace(/$index/g, $index);
                        return eval(str);

                    });
                }

                let e = $(generateHTML());

                if (!f.views[$index]) {
                    f.views[$index] = {
                        element: e,
                        inList: true,
                        item: item
                    }
                    f.container.append(e);
                    e.show();
                } else {
                    f.views[$index].inList = true;
                }
                
                // When item has been added or removed, update all
                if (f.count != list.length) {
                    f.views[$index].element.replaceWith(e);
                    f.views[$index].element = e;
                    e.show();
                    f.views[$index].item = item;
                    f.views[$index].inList = true;
                }

            }
            f.count = list.length;

            for(let $index = 0; $index < list.length; ++$index) {
                if (f.views[$index]) {
                    if (!f.views[$index].inList) {
                        let v = f.views[$index];
                        if (!v.inList) {
                            console.log("REMOVING", $index);
                            v.element.remove();
                            delete f.views[$index];
                        }
                    }
                }
            }

        }

        this.refresh();

    }

    refresh(e = null, target = null) {

        let body = e ? e : $("body");

        let ref = this;

        let code = "";
        if (target) {
            Object.keys(target).forEach(k => {
                code += "let " + k + " = target['" + k + "']; ";
            });
        }

        let elements = body.find("*[var]");
        elements.each(function() {
            let varname = $(this).attr("var");
            let value = ref.getProp(varname, target);

            ref.setViewValue($(this), value);
        });

        // conditions
        elements = body.find("*[if]");
        elements.each(function(){

            let condition = $(this).attr("if");

            let transition = $(this).attr("transition");
            let duration = $(this).attr("transition-duration");

            transition = ["fade", "slide"].includes(transition) ? transition : "auto";
            duration = duration ? parseInt(duration) : 200;

            let c = {
                element: $(this),
                condition: condition,
                transition: transition,
                duration: duration
            };

            let b = false;
            try { 
                eval(code + "b = " + c.condition); 

                if (c.transition == "fade") {
                    if (b) c.element.fadeIn(c.duration);
                    if (!b) c.element.fadeOut(c.duration);
                }
                else if (c.transition == "slide") {
                    if (b) c.element.slideDown(c.duration);
                    if (!b) c.element.slideUp(c.duration);
                }
                else {
                    if (b) c.element.show();
                    if (!b) c.element.hide();
                }
            
            } catch(e) {
                console.log("ERROR for conditional if ", c, e);
            }

        });

        // classes
        elements = body.find("*[_class]");
        elements.each(function(){

            let condition = $(this).attr("_class");
            let c = {
                element: $(this),
                json: ref.toJson(condition)
            };

            Object.keys(c.json).forEach( k => {
                let condition = c.json[k];
                let b = false;

                try {
                    eval(code + "b = " + condition); 

                    if (b) c.element.addClass(k);
                    else c.element.removeClass(k);
                
                } catch(e) {  }
            });

        });

        // styles
        elements = body.find("*[_style]");
        elements.each(function() {

            let prop = $(this).attr("_style");
            let s = {
                element: $(this),
                json: ref.toJson(prop)
            };

            Object.keys(s.json).forEach( k => {

                let b = false;
                try {
                    eval(code + "b = " + s.json[k]);
                    s.element.css( k, b );
                } catch(e){ }

            });
        });

    }


    /////////// Extras

    Form(args) {

        let form = {
            _clear: () => {},
            submit: function() {

                if (args.ajax) {
                    Ajax.request({
                        method: args.method ? args.method : "POST",
                        action: args.ajax,
                        onSuccess: response => {
                            args.onSubmit(response);
                            form._clear();
                        },
                        onError: args.onError ? args.onError : null,
                        data: args.data ? args.data() : {}
                    });
                }
                else {
                    args.onSubmit();
                    form._clear();
                }

                return form;
            },
            bindButton: function(selector) {
                $(selector).click(() => {
                    form.submit();
                });
                return form;
            },
            bindForm: function(selector) {
                $(selector).on('submit', function(e){
                    e.preventDefault();
                    form.submit();
                });
                return form;
            },
            clear: function(onClear) {
                form._clear = () => {
                    onClear();
                    Data.Refresh();
                };
                return form;
            }
        }
        return form;

    }
}

var Data = new DataBinding();

// JQuery
onInsertElement(e => {
    if (!e || !e['find']) return;
    Data.analyze(e);
});