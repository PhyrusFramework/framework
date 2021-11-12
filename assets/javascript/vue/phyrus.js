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
    objects: [],
    components: {},
    listeners: [],
    loaded: false,
    baseComponent: null,
    initBaseComponent() {
        let translations = window.translations ? window.translations : {}
        let win = window;
        let con = console;

        this.baseComponent = Vue.extend({
            // Base component
            data() {
                return {
                    window: win,
                    console: con,
                    Time: function(date, format) {
                        return new Time(date, format);
                    }
                }
            },
            methods: {
                $t(key, params = {}) {
                    let val = Utils.dotNotation(translations, key);

                    if (typeof val == 'string') {
                        Object.keys(params).forEach(k => {
                            val = val.replace('{{' + k + '}}', params[k]);
                        });
                    }

                    return val ? val : '';
                }
            }
        });
    },
    createData(data) {
        let mixins = data['mixins'];
        if (mixins) data.mixins.push(this.baseComponent);
        else data['mixins'] = [this.baseComponent];
    }
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
    _vue.initBaseComponent();

    for(let listener of _vue.listeners) {
        listener();
    }

    Object.keys(_vue.components)
    .forEach(key => {
        Vue.component(key, _vue.baseComponent.extend(_vue.components[key]) );
    });

    let mixins = [_vue.baseComponent];
    for(let data of _vue.objects) {
        mixins.push(Vue.extend(data));
    }

    window['View'] = new Vue({
        el: '#app-body',

        mixins: mixins
    });

    let loader = document.getElementById('vue-loader');
    if (loader) {
        loader.classList.add('fading');
        setTimeout(() => {
            loader.remove();
        }, 200);
    }
}

class VueController {
    constructor(data) {
        _vue.objects.push(data);
    }
}

class VueComponent {
    constructor(name, data) {
        if (_vue.loaded) {
            Vue.component(name, _vue.createData(data));
        }
        else {
            _vue.components[name] = data;
        }
    }
}