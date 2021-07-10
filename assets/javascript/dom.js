
var DomElements = {
    init: (body = null) => {
        let e = body ? body : $("body");
        DomElements.icons(e);
        DomElements.images(e);
    },

    icons: body => {

        body.find("icon").each(function() {
    
            let src = $(this).attr("src");
            if (src) {
    
                $(this).styles({
                    "mask-image": "url('" + src + "')",
                    "-webkit-mask-image": "url('" + src + "')"
                });
            }
    
            let color = $(this).attr("color");
            if (color) {
                $(this).css("background-color", color);
            } else {
                $(this).css("background-color", $(this).css('color')); 
            }
        
        });
    },

    images: body => {

        body.find("*[image]").each(function() {
            let attr = $(this).attr("image");
            if (!attr) return;
            $(this).styles({
                "background-image": "url('"+attr+"')",
                "background-position": "center",
                "background-size": "cover",
                "background-repeat": "no-repeat"
            });
        });

    }
}

DomElements.init();

onInsertElement(e => {
    DomElements.init(e);
});