
var http = {

    request(req) {

        return new Promise((resolve, reject) => {

            let xhr = new XMLHttpRequest();

            let format = req.format ? req.format : 'json';

            xhr.onload = function() {

                let data = xhr.response;

                if (format == 'json') {
                    try {
                        data = JSON.parse(data);
                    } catch(e) {
                        data = xhr.response;
                    }
                }       

                resolve(data);
            }

            xhr.onerror = function() {
                reject({
                    status: xhr.status,
                    response: xhr.response
                });
            }

            let url = req.url;
            if (!url) return;

            if (['GET', 'DELETE'].includes(req.method)) {
                let char = "?";
                Object.keys(req.data).forEach(key => {
                    url += char + key + "=" + req.data[key];
                    char = "&";
                });
            }

            xhr.open(req.method, url);
            let data = req.data;

            if (format == 'json') {
                xhr.setRequestHeader('Content-Type', 'application/json');
            } else if (format == 'media') {
                let d = new FormData();
                Object.keys(data)
                .forEach(k => {
                    d.append(k, data[k]);
                });
                data = d;
            }

            if (req.headers) {
                Object.keys(req.headers).forEach(name => {
                    xhr.setRequestHeader(name, req.headers[name]);
                });
            }

            xhr.send(data);

        });
    },

    get(url, req = {}) {

        req['method'] = 'GET';
        req['url'] = url;
        if (!req.data) {
            req.data = {};
        }

        return http.request(req);
    },

    post(url, req = {}) {

        req['method'] = 'POST';
        req['url'] = url;
        if (!req.data) {
            req.data = {};
        }

        return http.request(req);
    },

    delete(url, req = {}) {

        req['method'] = 'DELETE';
        req['url'] = url;
        if (!req.data) {
            req.data = {};
        }

        return http.request(req);
    },

    put(url, req = {}) {

        req['method'] = 'PUT';
        req['url'] = url;
        if (!req.data) {
            req.data = {};
        }

        return http.request(req);
    },

    patch(url, req = {}) {

        req['method'] = 'PATCH';
        req['url'] = url;
        if (!req.data) {
            req.data = {};
        }

        return http.request(req);
    }

}

function uploadFile(options)
{
    return new Promise((resolve, reject) => {

        let file = null;
        if (options.file) {
            file = options.file;
        } else {
            let inp = $(options.selector);
            if (inp.length == 0) return;
            file = inp.target.files[0];
        }
    
        if (!file) {
            reject("fileEmpty");
            return;
        }
    
        if (options.maxSize && (file.size / 1000000) > options.maxSize) {
            reject("sizeExceeded");
            return;
        }
    
        let data = {}
    
        let fname = "uploadedfile";
        if (options.filename) fname = options.filename;

        data[fname] = file;

        let url = options.url;
        if (options.action) {
            data['ajaxActionName'] = options.action;
            url = location.href;
        }

        if (!url) {
            reject("urlEmpty");
            return;
        }
    
        if (options.data) {
            Object.keys(options.data).forEach(k =>{
                data[k] = options.data[k];
            });
        }

        return http.request({
            method: options.method ? options.method.toUpperCase() : 'POST',
            url: url,
            data: data,
            format: 'media'
        });

    });
}
