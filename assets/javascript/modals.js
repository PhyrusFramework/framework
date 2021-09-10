var Modal  = {

    currents: [],

    close: () => {
        if (Modal.currents.length == 0) {
            return;
        }

        let last = Modal.currents[Modal.currents.length - 1];
        Modal.currents.splice(Modal.currents.length - 1, 1);
        last.close();
    },

    create: () => {

        let body = $("<div class='modal-container' style='display: none'></div>");
        let box = $("<div class='modal-box'></div>");
        body.append(box);

        let obj = {
            body: body,
            transition: 200,
            width: "50%",
            minHeight: null,
            maxHeight: "80%",
            class: "",
            id: "",
            cancelable: true,
            isReady: false,

            _whenReady: null,
            whenReady: callback => {
                obj._whenReady = callback;
                return obj;
            },
            _onClose: null,
            onClose: callback => {
                obj._onClose = callback;
                return obj;
            },

            setContent: html => {
                box.empty();

                if (typeof html == "string")
                    box.html(html);
                else
                    box.append(html);

                obj.isReady = true;
                if (obj._whenReady)
                    obj._whenReady(obj);

                return obj;
            },
            open: () => {
                box.css("width", obj.width);
                if (obj.minHeight) {
                    box.css("min-height", obj.minHeight);
                }
                if (obj.maxHeight) {
                    box.css("max-height", obj.maxHeight);
                }
                if (obj.class && obj.class != "") {
                    body.addClass(obj.class);
                }
                if (obj.id && obj.id != "") {
                    body.attr("id", obj.id);
                }

                $("body").append(body);
                body.fadeIn(obj.transition);
                Modal.currents.push(obj);
                return obj;
            },
            close: (param = null) => {
                body.fadeOut(obj.transition);
                setTimeout(() => {
                    body.remove();
                }, obj.transition);

                if (obj._onClose)
                    obj._onClose(param);

                return obj;
            }
        }

        body.click(function() {
            if (obj.cancelable)
                obj.close();
        });

        box.click(function(e) {
            e.stopPropagation();
        });

        return obj;
    },

    fromHTML: (html) => {
        return Modal.create().setContent(html);
    },

    fromAjax: (functionName, parameters = {}) => {

        let modal = Modal.create();

        Ajax.post(functionName, parameters).then(res => {

            modal.setContent(res);

        });

        return modal;
    },

    fromComponent: (component, parameters = {}) => {

        return Modal.fromAjax("_modal_get_component", {component: component, parameters: parameters});

    }

}