
var Ajax = {

    request: request => {

        return new Promise((resolve, reject) => {

            request.data['ajaxActionName'] = request.action;

            let xhr = new XMLHttpRequest();

            xhr.onload = function() {
                let data = xhr.response;

                let result = data;
                try {
                    result = JSON.parse(data);
                    if (!result) {
                        result = data;
                    }
                } catch (e) {
                    result = data;
                }

                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve(result);
                    return;
                }

                reject({
                    status: xhr.status,
                    message: xhr.statusText,
                    data: result
                });
            }

            xhr.onerror = function() {
                reject({
                    status: xhr.status,
                    message: xhr.statusText
                });
            }

            let url = location.href;
            if (!url) return;

            let method = request.method ? request.method.toUpperCase() : "POST";

            let contentType = request.contentType ? request.contentType : 'application/json';

            if (['GET', 'DELETE'].includes(method)) {
                let char = "?";
                Object.keys(request.data).forEach(key => {
                    url += char + key + "=" + request.data[key];
                    char = "&";
                });
            } else {
                if (contentType == 'application/json') {
                    request.data = JSON.stringify(request.data);
                }
            }

            xhr.open(method, url);
            xhr.setRequestHeader('Content-Type', contentType);

            if (request.headers) {
                Object.keys(request.headers).forEach(name => {
                    xhr.setRequestHeader(name, request.headers[name]);
                });
            }

            xhr.send(request.data);

        });

    },

    post: (action, data = {}) => {
        return Ajax.request({
            method: 'POST',
            action: action,
            data: data
        });
    },

    get: (action, data = {}) => {

        return Ajax.request({
            method: 'GET',
            action: action,
            data: data
        });
    }

}