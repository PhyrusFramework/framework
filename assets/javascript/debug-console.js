VueLoaded(() => {
    new Vue({
        el: '#debug-console',
    
        data() {
            return {
                show: false,
                displayLogs: []
            }
        },
    
        created() {
            document.body.addEventListener('keyup', (e) => {
                if (e.key == 'º') {
                    this.show = !this.show;
                }
            });
        },

        methods: {
            setLogCount(n) {
                if (this.displayLogs.length == 0 && n > 0) {
                    for(let i = 0; i < n; ++i) {
                        this.displayLogs.push(false);
                    }
                }
                return false;
            },
            displayLog(n) {
                if (this.displayLogs.length <= n) {
                    return false;
                }
                return this.displayLogs[n];
            },
            setLogVisible(n) {
                if (this.displayLogs.length <= n) {
                    return;
                }
                this.displayLogs[n] = !this.displayLogs[n];
                this.$forceUpdate();
            }
        }
    });
});