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

function force(values, def) {
    if (!values) return def;
    var obj = {};
    Object.keys(def).forEach(key => {
        if (values[key] === null || values[key] === undefined) obj[key] = def[key];
        else obj[key] = values[key];
    });
    return obj;
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

function hexToRGB(hex) {
    var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
      r: parseInt(result[1], 16),
      g: parseInt(result[2], 16),
      b: parseInt(result[3], 16)
    } : null;
}

function RGBToHex(r, g, b) {
    return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
}

function replacer(text, delimiterLeft, delimiterRight, replacer) {

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

function sanitize(text) {
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