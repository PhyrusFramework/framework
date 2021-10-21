
class http {

    static request(req) {

        return new Promise((resolve, reject) => {

            let xhr = new XMLHttpRequest();

            let format = req.format ? req.format : 'json';

            xhr.onload = function() {

                let data = xhr.response;

                if (format == 'json') {
                    data = JSON.parse(data);
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

            if (format == 'json') {
                xhr.setRequestHeader('Content-Type', 'application/json');
            } else if (format == 'media') {
                xhr.setRequestHeader('Content-Type', 'multipart/form-data');
            }

            if (req.headers) {
                Object.keys(req.headers).forEach(name => {
                    xhr.setRequestHeader(name, req.headers[name]);
                });
            }

            xhr.send(req.data);

        });
    }

    static get(url, req = {}) {

        req['method'] = 'GET';
        req['url'] = url;
        if (!req.data) {
            req.data = {};
        }

        return http.request(req);
    }

    static post(url, req = {}) {

        req['method'] = 'POST';
        req['url'] = url;
        if (!req.data) {
            req.data = {};
        }

        return http.request(req);
    }

    static delete(url, req = {}) {

        req['method'] = 'DELETE';
        req['url'] = url;
        if (!req.data) {
            req.data = {};
        }

        return http.request(req);
    }

    static put(url, req = {}) {

        req['method'] = 'PUT';
        req['url'] = url;
        if (!req.data) {
            req.data = {};
        }

        return http.request(req);
    }

    static patch(url, req = {}) {

        req['method'] = 'PATCH';
        req['url'] = url;
        if (!req.data) {
            req.data = {};
        }

        return http.request(req);
    }

}