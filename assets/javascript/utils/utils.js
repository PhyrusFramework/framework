var URL = {

    current: () => {
        return location.href;
    },

    host: () => {
        return window.location.protocol + "//" + window.location.hostname;
    },

    hostname: () => {
        return window.location.hostname;
    },

    parameters: (url = null) => {
        let u = url ? url : window.location.href;

        var vars = [], hash;
        if (!u.includes("?")) return vars;
        
        var hashes = (u.slice(u.indexOf('?') + 1)).split('&');
        for(var i = 0; i < hashes.length; i++)
        {
            hash = hashes[i].split('=');
            vars[hash[0]] = hash[1];
        }
        return vars;
    },

    parameter: (name, def = null) => {
        let params = URL.parameters();
        return params[name] ? params[name] : null;
    },

    setParameters: (params, url = null, currentParameters = {}) => {

        let u = url ? url : window.location.href;
        let base = u;
        if (base.includes("?"))
            base = base.split('?')[0];

        let currents = currentParameters;

        Object.keys(params).forEach(k => {
            currents[k] = params[k];
        });

        if (Object.keys(currents).length == 0) return base;

        let symb = "?";
        Object.keys(currents).forEach((k)=>{
            base += symb + k + "=" + currents[k];
            symb = "&";
        });

        return base;

    },

    addParameters: (params, url = null) => {
        
        URL.setParameters(params, url, URL.parameters(url ? url : location.href));

    },

    set: (url, title = null) => {
        window.history.pushState("", "", url);
        if (title) {
            document.title = title;
        }
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
    
        if (!file || !options.url) return;
    
        if (options.maxSize && (file.size / 1000000) > options.maxSize) {
            reject("sizeExceeded");
            return;
        }
    
        let formData = new FormData();
    
        let fname = "uploadedfile";
        if (options.filename) fname = options.filename;
    
        formData.append(fname, file);
    
        if (options.data) {
            Object.keys(options.data).forEach((k)=>{
                formData.append(k, options.data[k]);
            });
        }

        $.ajax({
            type: options.method ? options.method.toUpperCase() : 'POST',
            url: options.url,
            success: resolve,
            error: reject,
            async: true,
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            headers: options.headers ? options.headers : {},
            timeout: options.timeout ? options.timeout : 60000
        });

    });
}

function iterate(obj, callback) {
    Object.keys(obj).forEach( k => {
        v = obj[k];
        callback(k, v);
    });
}

function empty(value) {
    if (value === null) return true;
    if (value === undefined) return true;
    if (value == "") return true;
    if (Array.isArray(value) && value.length == 0) return true;
    return false;
}

class Utils {

    static force(values, def) {
        if (!values) return def;
        var obj = {};
        Object.keys(def).forEach(key => {
            if (values[key] === null || values[key] === undefined) obj[key] = def[key];
            else obj[key] = values[key];
        });
        return obj;
    }

    static hexToRGB(hex) {
        var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
          r: parseInt(result[1], 16),
          g: parseInt(result[2], 16),
          b: parseInt(result[3], 16)
        } : null;
    }
    
    static RGBToHex(r, g, b) {
        return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
    }    

    static replacer(text, delimiterLeft, delimiterRight, replacer) {

        let str = '';
        current = '';
        let invar = false;
        let currentVar = '';

        for(let i = 0; i<text.length; ++i) {

            let ch = text[i];

            if (!invar) {

                if (current.length >= delimiterLeft.length) {
                    str += current[0];
                    current = current.substr(1) + ch;
                } else {
                    current += ch;
                }

                if (current == delimiterLeft) {
                    invar = true;
                    current = "";
                }

            }
            else {

                if (current.length >= delimiterRight.length) {
                    currentVar += current[0];
                    current = current.substr(1) + ch;
                } else {
                    current += ch;
                }

                if (current == delimiterRight) {
                    str += replacer(currentVar);
                    currentVar = '';
                    invar = false;
                    current = '';
                }

            }

        }
        str += current;
        return str;
    }

    static sanitize(text) {
        let t = text.toLowerCase();

        let changes = {
            ' ' : '-',
            '¿' : '',
            '\\?' : '',
            '\\*' : '',
            ',' : '',
            '\\.' : '',
            '\\(' : '',
            '\\)' : '',
            '\\[' : '',
            '\\]' : '',
            '!' : '',
            '¡' : '',
            'á' : 'a',
            'à' : 'a',
            'â' : 'a',
            'ä' : 'a',
            'é' : 'e',
            'è' : 'e',
            'ê' : 'e',
            'ë' : 'e',
            'í' : 'i',
            'ì' : 'i',
            'î' : 'i',
            'ï' : 'i',
            'ó' : 'o',
            'ò' : 'o',
            'ô' : 'o',
            'ö' : 'o',
            'ú' : 'u',
            'ù' : 'u',
            'û' : 'u',
            'ü' : 'u'
        };

        Object.keys(changes).forEach(k => {
            var re = new RegExp(k,"g");
            t = t.replace(re, changes[k]);
        });

        return t;
    }

    static dotNotation(arr, key, defaultValue = null) {
        if(!arr || !key) {
            return defaultValue !== undefined ? defaultValue : null;
        }

        if (!key.includes(".")) {
            if (arr[key]) return arr[key];
            return key;
        }
        let parts = key.split(".");
        let c = arr;
        for(let i = 0; i<parts.length - 1; ++i) {
            if (!c[parts[i]]) return defaultValue !== undefined  ? defaultValue : key;
            c = c[parts[i]];
        }
        let n = parts.length - 1;
        if (!c[parts[n]]) return defaultValue !== undefined  ? defaultValue : key;
        return c[parts[n]];
    }

    static invertList(arr) {
        let inv = [];

        for(let i = arr.length - 1; i >= 0; --i) {
            inv.push(arr[i]);
        }

        return inv;
    }

    static copy(obj, recursive = false) {
        let s = {};
        if (!obj) return s;
        Object.keys(obj).forEach((key) => {

            if (typeof s[key] == 'object') {
                if (recursive) {
                    s[key] = Utils.copy(obj[key], true);
                } else {
                    s[key] = obj[key];
                }
            } else {
                s[key] = obj[key];
            }
        });
        return s;
    }

    static validateEmail(email) {
        if (!email) return false;
        if (email == "") return false;
        if (email.length < 4) return false;

        let c1 = email.indexOf("@");
        let c2 = email.lastIndexOf(".");
        if (c1 < 0) return false;
        if (c2 < 0) return false;
        if (c2 < c1) return false;

        return true;
    }

    static addToList(list, items, mapFunc = null) {
        let l = list;
        if (!l) l = items;
        else {
            for(let n of items) {
                if (mapFunc) {
                    l.push(mapFunc(n));
                } else {
                    l.push(n);
                }
            }
        }
        return l;
    }

    static randomString(length = 10) {
        return Math.random().toString(36).substr(2, length);
    }

    static rand(max = 100) {
        return Math.random() * max;
    }

    static force(obj, defaultValues) {

        let res = {};

        Object.keys(defaultValues).forEach((k) => {
            res[k] = obj[k] ? obj[k] : defaultValues[k];
        });

        return res;

    }

    static merge(objA, objB, mergeArrays = false) {

        let aux = {};

        Object.keys(objA).forEach((key) => {

            if (typeof objA[key] == 'object') {

                if (objB[key] === undefined) {
                    aux[key] = objA[key];
                } else if (typeof objB[key] == 'object') {
                    aux[key] = Utils.merge(objA[key], objB[key]);
                } else {
                    aux[key] = objB[key];
                }

            } else if (Array.isArray(objA[key])) {

                if (objB[key] === undefined) {
                    aux[key] = objA[key];
                } else if (Array.isArray(objB[key]) && mergeArrays) {
                    let list = [];
                    for(let item of objA[key]) {
                        aux[key].push(item);
                    } 
                    for(let item of objB[key]) {
                        aux[key].push(item);
                    }
                    aux[key] = list;
                } else {
                    aux[key] = objB[key];
                }

            } else {

                if (objB[key] !== undefined) {
                    aux[key] = objB[key];
                } else {
                    aux[key] = objA[key];
                }

            }

        });

        Object.keys(objB).forEach((key) => {

            if (aux[key] === undefined) {
                aux[key] = objB[key];
            }

        });

        return aux;

    }

    static scrollBottomReached(e, threshold = 50) {

        let top = e.target.scrollTop;
        let height = e.target.clientHeight;
        let scrollHeight = e.target.scrollHeight;
        let umbral = threshold ? threshold : 50;

        if (top + height >= scrollHeight - umbral) {

            return true;

        }

        return false;

    }

    /**
     * Convert a file from an input in a src for an img tag.
     * 
     * @param file
     */
    static fileToSrc(file) {

        return new Promise((resolve, reject) => {

            let reader = new FileReader();
        
            reader.onloadend = function () {
                resolve(reader.result);
            }

            reader.onerror = function() {
                reject();
            }
            
            reader.readAsDataURL(file);

        });
    
    }

    static capitalize(text) {
        return text[0].toUpperCase() + text.substr(1);
    }

     static randomImage(width = 250, height = 250, seed = null) {
        return 'https://picsum.photos/seed/' + (seed ? seed : this.randomString(4)) + '/' + width + '/' + height;
    }
    
    static hasExtension(filename, extensions) {

        let list = Array.isArray(extensions) ? extensions : [extensions];

        for(let s of list) {
            if (filename.includes('.' + s)) {
                return true;
            }
        }

        return false;
    }

}

class Cookie {

    static set(name, value) {
        document.cookie = name +'='+ value +'; Path=/;';
    }

    static delete(name) {
        document.cookie = name +'=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
    }

}