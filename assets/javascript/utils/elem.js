
function elem(element) {
    return new Elem(element);
}

class Elem {
    
    constructor(e) {

        if (typeof e == 'string') {

            if (e == '') return;

            if (e[0] == '#') {
                this.element = document.getElementById(e.substr(1));
            }
            else if (e[0] == '.') {
                this.element = document.getElementsByClassName(e.substr(1))[0];
            }
            else {
                this.element = document.getElementsByTagName(e);
            }

        } else {
            this.element = e;
        }

    }

    css(key, value) {
        this.element.style[key] = value;
    }

    style(styles) {
        Object.keys(styles).forEach(k => {
            this.element.style[k] = styles[k];
        })
    }

    attr(name, value = null) {

        if (value) {
            this.element.setAttribute(name, value);
        }

        return this.element.getAttribute(name);
    }

    getAttributes() {
        let attrs = this.element.attributes;
        let obj = {}
        for(let attr of attrs) {
            obj[attr] = this.element.getAttribute(attr);
        }
        return obj;
    }

    hasAttr(name) {
        let f = !this.attr(name);
        return !f;
    }

    getFile() {
        if (this.element.files.length == 0)
            return null;
        return this.element.files[0];
    }

    getFiles() {
        return this.element.files;
    }

    get tag() {
        return this.element.tagName;
    }

    get offset() {
        return {
            top: this.element.offsetTop,
            left: this.element.offsetLeft,
            right: this.element.offsetRight,
            bottom: this.element.offsetBottom
        }
    }

    get scroll() {
        return {
            top: this.element.scrollTop,
            left: this.element.scrollLeft,
            height: this.element.scrollHeight,
            width: this.element.scrollWidth
        }
    }

    get height() {
        return this.element.offsetHeight;
    }

    get width() {
        return this.element.offsetWidth;
    }

    initScrollEvent(ev) {
        let interval = setInterval(() => {
            if (ev()) {
                clearInterval(interval);
            }
        }, 1);

        let event = () => {
            clearInterval(interval)
            window.removeEventListener('mousewheel', event);
            window.removeEventListener('mousedown', event);
            window.removeEventListener('touchstart', event);
        }

        window.addEventListener('mousewheel', event);
        window.addEventListener('mousedown', event);
        window.addEventListener('touchstart', event);
    }

    scrollHere(animated = true) {
        if (!animated) {
            window.scrollTo(0, this.offset.top);
            return;
        }

        let lastDiff = {
            position: window.pageYOffset || document.documentElement.scrollTop,
            value: -1
        }

        this.initScrollEvent(() => {

            let win = lastDiff.position;
            let dest = this.offset.top;
            let diff = dest - win;
            let diffAbs = diff < 0 ? diff * -1 : diff;

            let deltaAbs = lastDiff.value - diffAbs;
            deltaAbs = deltaAbs < 0 ? deltaAbs * -1 : deltaAbs;

            if (lastDiff.value > 0 && deltaAbs < 0.1) {
                return true;
            } else {
                lastDiff.value = diffAbs;

                let slowLimit = 4;

                let delta = diff / 100;
                let deltaAbs = delta > 0 ? delta : delta * -1;
                if (deltaAbs < slowLimit) {
                    delta = delta > 0 ? slowLimit : -slowLimit;
                    deltaAbs = delta > 0 ? delta : delta * -1;
                }
                if (deltaAbs > diffAbs) {
                    delta = diff;
                }

                lastDiff.position += delta;
                let x = window.pageXOffset || document.documentElement.scrollLeft;
                window.scrollTo(x, lastDiff.position);
            }
            return false;


        });

    }


    scrollTo(y, animated = false) {

        if (!animated) {
            this.element.scrollTop = y;
            return;
        }

        this.initScrollEvent(() => {
            let current = this.element.scrollTop;
            let diff = y - current;
            let diffAbs = diff < 0 ? diff * -1 : diff;

            if (diffAbs < 10 || current >= this.scroll.height - this.height - 5) {
                return true;
            } else {
                this.scrollTo(current + diff / 100)
            }

            return false;
        });

    }

    scrollToBottom(animated = true) {
        this.scrollTo(this.scroll.height, animated);
    }

    scrollToTop(animated = true) {
        this.scrollTo(0, animated);
    }

    scrollToChild(child, animated = true) {
        let e = typeof child == 'string' ? elem(child) : child;

        let relY = e.offset.top - this.offset.top;

        this.scrollTo(relY, animated);
    }

}