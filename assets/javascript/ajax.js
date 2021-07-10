
var Ajax = {

    request: request => {

        return new Promise((resolve, reject) => {

            request.data['ajaxActionName'] = request.action;
        
            jQuery.ajax({
                data:  request.data,
                url:   location.href + (request.parameters ? request.parameters : ""),
                type:  request.method ? request.method.toLowerCase() : "post",
                method: request.method ? request.method.toUpperCase() : "POST",
                success:  resolve,
                error: reject,
                contentType: request.contentType ? request.contentType : 'application/x-www-form-urlencoded; charset=UTF-8',
                headers: request.headers ? request.headers : {},
                timeout: request.timeout ? request.timeout : 0
            });

        });

    },

    post: (action, data = {}) => {
        return Ajax.request({
            method: 'post',
            action: action,
            data: data
        });
    },

    get: (action, data = {}) => {

        let parameters = "";
        let symb = "?";
        Object.keys(data).forEach( k => {
            parameters += symb + k + "=" + data[k];
            symb = "&";
        });

        return Ajax.request({
            method: 'get',
            action: action,
            data: data,
            parameters: parameters
        });
    }

}

let checkElementAjaxAttribute = e => {
    e.each(function() {
        let ajax = $(this).attr("ajax");
        if (ajax) {
            $(this).removeAttr("ajax");
            let line = "$(this).ajax(" + ajax + ")";
            eval(line);
        }

        let els = $(this).find("*[ajax]");
        els.each(function() {
            let ajax = $(this).attr("ajax");
            $(this).removeAttr("ajax");
            let line = "$(this).ajax(" + ajax + ")";
            eval(line);
        });
    });
}

$(document).ready(function() {

    checkElementAjaxAttribute($("*[ajax]"));

    onInsertElement(e => {
        checkElementAjaxAttribute(e);
    });

});

$.fn.ajax = function() {
    let action = arguments[0];
    let params = arguments.length > 1 ? arguments[1] : {};

    Ajax.post(action, params).then(res => {
        this.empty();
        let e = $(res);
        this.append(e);

        if (arguments.length > 2) {
            arguments[2](e);
        }
    });
}

$.fn.reloadAjax = function() {
    let e = arguments[0];
    this.before(e);
    this.remove();
}