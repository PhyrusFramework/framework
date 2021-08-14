
class Component {

    static registeredComponents = {}

    static register(name, componentOptions) {

        if (!componentOptions.template) {
            return;
        }

        Component.registeredComponents[name] = componentOptions;

    }

    static init(node) {

        let name = node.component_name;
        if (!Component.registeredComponents[name]) {
            return;
        }

        let options = Component.registeredComponents[name];

        new Component(node, options);

    }

    node;
    options;

    constructor(node, options) {
        this.options = options;

        let template = options.template;

        if (template.html) {
            this.buildFromTemplate(options.template.html, options.data ? options.data : {}, node);
            return;
        }

        if (template.ajax) {

            let ajaxReq = template.ajax;

            if (typeof template.ajax == 'string') {
                ajaxReq = {
                    action: template.ajax,
                    method: 'GET'
                }
            }

            if (!ajaxReq.method || !['GET', 'POST'].includes(ajaxReq.method)) {
                ajaxReq['method'] = 'GET'
            }

            let func = Ajax.get;
            if (ajaxReq.method == 'POST') func = Ajax.post;

            func(ajaxReq.action, ajaxReq.data ? ajaxReq.data : {}).then(html => {

                this.node = node.replace($(html), options.data ? options.data : {});
                View.update();

            });
            return;
            
        }

    }

    buildFromTemplate(template, data, node) {

        let e = $(template);

        let attributes = node.elements.this.getAttributes();
        let dataa = {}

        Object.keys(attributes).forEach(k => {
            if (k == ':component') return;

            let attr = k;
            let useEval = false;
            if (attr.length > 0 && attr[0] == ':') {
                useEval = true;
                attr = attr.substr(1);
            }

            let val = '';
            if (useEval) {
                try {
                    eval('val = ' + attributes[k]);
                } catch(e) {}
            } else {
                val = attributes[k];
            }

            dataa[attr] = val;
        });

        Object.keys(data).forEach(k => {
            dataa[k] = data[k];
        });

        this.node = node.replace(e, dataa);
    }

}