function ready(cb) {
    $(document).ready(() => {
        cb();
    });
}

var __onElementInsertEvents = [];
function onInsertElement(callback) {
    __onElementInsertEvents.push(callback);
}

function prepareInsertionEvents() {
    let events = ['append', 'prepend', 'after', 'before'];

    for(let event of events) {
        let originalEvent = $.fn[event];

        $.fn[event] = function() {

            originalEvent.apply(this, arguments);

            if (arguments.length > 0 && arguments[0]['find']) {
                for(let ev of __onElementInsertEvents) {
                    ev(arguments[0]);
                }
            }

        }
    }
}
prepareInsertionEvents();

$.fn.onPressEnter = function() {

    let cb = arguments[0];
    let e = this;

    this.keyup(function(k) {
        if (k.keyCode == 13) {
            cb(e);
        }
    });

}

$.fn.styles = function() {

    let styles = arguments[0];

    Object.keys(styles).forEach( k => {
        this.css(k, styles[k]);
    });

}

$.fn.fadeRemove = function() {

    let time = arguments.length > 0 ? arguments[0] : 200;

    this.fadeOut(time);
    setTimeout(() => {
        this.remove();

        if (arguments.length > 1) {
            arguments[1]();
        }
    }, time);

}

$.fn.fromAjax = function() {

    let ops = arguments.length > 1 ? arguments[1] : {};

    if (ops.replace) {
        this.empty();
    }

    if (ops.fadeIn)
        e.css("display", "none");

    Ajax.get(arguments[0], ops, response => {
        let e = $(response);

        this.append(e);

        if (ops.fadeIn) {
            e.fadeIn(ops.transition ? ops.transition : 200);
        }
    });
}

$.fn.replace = function() {
    let e = arguments[0];
    this.before(e);
    this.remove();
}

$.fn.getAttributes = function() {

    var attrs = {};

    this.each(function() {

        let attributes = this.attributes;
        Object.keys(attributes).forEach( k => {
            let name = attributes[k].name;
            let value = attributes[k].nodeValue;

            attrs[name] = value;
        });

    });
    
    return attrs;
}

$.fn.hasAttr = function() {
    var attr = this.attr(arguments[0]);

    if (!attr) {
        attr = this.attr(arguments[0].toLowerCase());
    }

    return typeof attr !== typeof undefined && attr !== false;
}

$.fn.andSelf = function() {
    this.add(this.prevObject);
    return this;
}

$.fn.addOption = function(value, label = null) {

    let text = label ? label : value;

    let tag = this.prop("tagName").toLowerCase();
    let select = tag == 'select' ? this : this.find("select");

    select.append($("<option value='" + value + "'>" + text + "</option>"));

}

$.fn.removeOption = function(value) {

    let tag = this.prop("tagName").toLowerCase();
    let select = tag == 'select' ? this : this.find("select");

    select.find("option[value='" + value + "']").remove();

}

$.fn.getOptions = function() {

    let options = this.find("options");
    let obj = {};

    options.each(function() {

        obj[$(this).val()] = $(this).text();

    });

    return obj;

}

$.fn.checked = function() {
    if (arguments.length == 0)
    return this.is(":checked");

    checked = arguments[0];
    return this.prop('checked', checked);
}

$.fn.getFile = function() {
    if (this.length == 0) return null;
    return this.target.files[i];
}

$.fn.visible = function() {
    return this.is(':visible');
}

$.fn.tag = function() {
    if (this.length == 0) return '';
    return this.prop('tagName');
}