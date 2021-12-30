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

const VueLoader = {
    objects: [],
    components: {},
    listeners: [],
    loaded: false,
    pageLoaded: false,
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
            },
            computed: {
                $route: function() {
                    return window.$route ? window.$route : {}
                }
            }
        });
    },
    createData(data) {
        let mixins = data['mixins'];
        if (mixins) data.mixins.push(this.baseComponent);
        else data['mixins'] = [this.baseComponent];
    },
    didLoad() {
        this.loaded = true;
        if (this.pageLoaded) {
            this.create();
        }
    },
    pageLoaded() {
        this.pageLoaded = true;
        if (this.loaded) {
            this.create();
        }
    },
    create() {
        this.initBaseComponent();
    
        for(let listener of this.listeners) {
            listener();
        }
    
        Object.keys(this.components)
        .forEach(key => {
            Vue.component(key, this.baseComponent.extend(this.components[key]) );
        });
    
        let mixins = [this.baseComponent];
        for(let data of this.objects) {
            mixins.push(Vue.extend(data));
        }

        window['View'] = new Vue({
            el: '#app-body',

            mixins: mixins
        });

        let loader = document.getElementById('vue-loader');
        if (loader) {
            setTimeout(() => {
                loader.classList.add('fading');
                setTimeout(() => {
                    loader.remove();
                }, 100);
            }, 10);
        }
    
    }
}

function VueLoaded(callback) {
    if (VueLoader.loaded) {
        callback();
        return;
    }
    VueLoader.listeners.push(callback);
}

class VueController {
    constructor(data) {
        VueLoader.objects.push(data);
    }
}

class VueComponent {
    constructor(name, data) {
        if (VueLoader.loaded) {
            Vue.component(name, VueLoader.createData(data));
        }
        else {
            VueLoader.components[name] = data;
        }
    }
}