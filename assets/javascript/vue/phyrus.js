const _PageLoadCallbacks = [];
window.addEventListener('load', function() {
    for(let cb of _PageLoadCallbacks) {
        cb();
    }
});

function PageLoaded(callback) {
    if (document.readyState == 'complete') {
        callback();
        return;
    }
    _PageLoadCallbacks.push(callback);
}

const _vue = {
    objects: {},
    components: {},
    listeners: [],
    loaded: false
}

function VueLoaded(callback) {
    if (_vue.loaded) {
        callback();
        return;
    }
    _vue.listeners.push(callback);
}

function _vueLoaded() {

    _vue.loaded = true;
    for(let listener of _vue.listeners) {
        listener();
    }

    Object.keys(_vue.components)
    .forEach(key => {
        Vue.component(key, _vue.components[key]);
    });

    window['View'] = new Vue({
        el: '#app-body',
        data() {
            let d = {}

            Object.keys(_vue.objects).forEach(name => {
                d[name] = _vue.objects[name];
            });

            return d;
        },

        created() {
            Object.keys(_vue.objects).forEach(name => {
                if (_vue.objects[name]['__created']) {
                    _vue.objects[name]['__created']();
                }
            });
        },

        mounted() {
            Object.keys(_vue.objects).forEach(name => {
                if (_vue.objects[name]['__mounted']) {
                    _vue.objects[name]['__mounted']();
                }
            });
        }
    });
}

function VuePage(data) {
    VueAdd('page', data);
}

function VueAdd(name, data) {

    let object = {};

    if (data.data) {
        let d = data.data();
        Object.keys(data.data()).forEach(k => {
            object[k] = d[k];
        });
    }

    if (data.methods) {
        Object.keys(data.methods).forEach(k => {
            object[k] = data.methods[k];
        });
    }

    if (data.created) {
        object['__created'] = data.created;
    }

    if (data.mounted) {
        object['__mounted'] = data.mounted;
    }

    _vue.objects[name] = object;
}

function VueComponent(name, data) {
    if (_vue.loaded) {
        Vue.component(name, data);
    }
    else {
        _vue.components[name] = data;
    }
}