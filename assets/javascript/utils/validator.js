
class Validator {

    value;
    checks = [];

    constructor(value = null) {
        this.value = value;
    }

    static for(value) {
        return new Validator(value);
    }

    isString() {
        this.checks.push({
            function: function(value) {
                return typeof value == 'string';
            }
        });

        return this;
    }

    is(e) {

        this.checks.push({
            function: function(value, param) {
                return value === param;
            },
            parameter: e
        });

        return this;

    }

    isNot(e) {

        this.checks.push({
            function: function(value, param) {
                return value != param;
            },
            parameter: e
        });

        return this;

    }

    typeIs(type) {

        this.checks.push({
            function: function(value, param) {
                return typeof value == param;
            },
            parameter: type
        });

        return this;

    }

    typeIsNot(type) {

        this.checks.push({
            function: function(value, param) {
                return typeof value != param;
            },
            parameter: type
        });

        return this;

    }

    contains(e) {

        if (Array.isArray(this.value)) {
            this.checks.push({
                function: function(value, param) {
                    return value.includes(param);
                },
                parameter: e
            });
        } else {
            this.checks.push({
                function: function(value, param) {
                    return value.indexOf(param) >= 0;
                },
                parameter: e
            });
        }

        return this;

    }

    minLength(length) {
        this.checks.push({
            function: function(value, param) {
                return value.length >= param;
            },
            parameter: length
        });

        return this;
    }

    maxLength(length) {
        this.checks.push({
            function: function(value, param) {
                return value.length <= param;
            },
            parameter: length
        });

        return this;
    }

    isEmail() {
        this.checks.push({
            function: function(value) {
                if (typeof value != 'string') return false;
                if (value.length < 5) return false;
                if (!value.includes('@')) return false;
                if (!(value.split('@')[1]).includes('.') ) return false;

                return true;
            }
        });

        return this;
    }

    isUppercase() {
        this.checks.push({
            function: function(value) {
                if (typeof value != 'string') return false;
                return value == value.toUpperCase();
            }
        });

        return this;
    }

    isLowercase() {
        this.checks.push({
            function: function(value) {
                if (typeof value == 'string') return false;
                return value == value.toLowerCase();
            }
        });

        return this;
    }

    hasUppercase(min = 1) {
        this.checks.push({
            function: function(value, param) {
                if (typeof value != 'string') return false;

                let count = 0;

                for(let i = 0; i < value.length; ++i) {

                    if (value[i].toUpperCase() == value[i]) {
                        count += 1;

                        if (count >= param) {
                            return true;
                        }
                    }

                }

                return false;
            },
            parameter: min
        });

        return this;
    }

    hasNotUppercase() {
        this.checks.push({
            function: function(value) {
                if (typeof value == 'string') return true;

                for(let i = 0; i < value.length; ++i) {
                    if (value[i] == value[i].toUpperCase()) {
                        return false;
                    }
                }

                return true;
            }
        });

        return this;
    }

    hasLowercase(min = 1) {
        this.checks.push({
            function: function(value, param) {
                if (typeof value != 'string') return false;

                let count = 0;

                for(let i = 0; i < value.length; ++i) {
                    if (value[i] == value[i].toLowerCase()) {
                        count += 1;

                        if (count >= param) {
                            return true;
                        }
                    }
                }

                return false;
            },
            parameter: min
        });

        return this;
    }

    hasNotLowercase() {
        this.checks.push({
            function: function(value) {
                if (typeof value == 'string') return true;

                for(let i = 0; i < value.length; ++i) {
                    if (value[i] == value[i].toLowerCase()) {
                        return false;
                    }
                }

                return true;
            }
        });

        return this;
    }

    isNumber() {
        this.checks.push({
            function: function(value) {
                return typeof value == 'number';
            }
        });

        return this;
    }

    hasNumbers(min = 1) {

        this.checks.push({
            function: function(value, param) {

                let count = 0;

                for(let i = 0; i < value.length; ++i) {

                    if ('0123456789'.includes(value[i])) {
                        count += 1;

                        if (count >= param) {
                            return true;
                        }
                    }
                }

                return false;
            },
            parameter: min
        });

        return this;

    }

    hasNotNumbers() {

        this.checks.push({
            function: function(value) {

                for(let i = 0; i < value.length; ++i) {
                    if ('0123456789'.includes(value[i])) {
                        return false;
                    }
                }

                return true;
            }
        });

        return this;

    }

    isLetters() {
        this.checks.push({
            function: function(value) {
                return /^[a-zA-Z]+$/.test(value);
            }
        });

        return this;
    }

    hasLetters(min = 1) {
        this.checks.push({
            function: function(value) {
                if (typeof value != 'string') return false;

                let count = 0;

                for(let i = 0; i < value.length; ++i) {
                    if (/^[a-zA-Z]+$/.test(value[i])) {
                        count += 1;

                        if (count >= min) {
                            return true;
                        }
                    }
                }

                return false;
            }
        });

        return this;
    }

    hasNotLetters() {
        this.checks.push({
            function: function(value) {
                if (typeof value != 'string') return true;

                for(let i = 0; i < value.length; ++i) {
                    if (/^[a-zA-Z]+$/.test(value[i])) {
                        return false;
                    }
                }

                return true;
            }
        });

        return this;
    }

    isSpecialChars() {
        this.checks.push({
            function: function(value) {
                if (typeof value != 'string') return false;
                return ctype_punct(value);
            }
        });

        return this;
    }

    hasSpecialChars(min = 1) {
        this.checks.push({
            function: function(value, param) {
                if (typeof value != 'string') return false;

                let count = 0;

                for(let i = 0; i < value.length; ++i) {
                    if (/[ `!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~]/.test(value[i])) {
                        count += 1;

                        if (count >= param) {
                            return true;
                        }
                    }
                }

                return false;
            },
            parameter: min
        });

        return this;
    }

    hasNotSpecialChars() {
        this.checks.push({
            function: function(value) {
                if (typeof value != 'string') return true;

                for(let i = 0; i < value.length; ++i) {
                    if (/[ `!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~]/.test(value[i])) {
                        return false;
                    }
                }

                return true;
            }
        });

        return this;
    }

    isPhone() {

        this.checks.push({
            function: function(value) {

                let v = "" + value;

                let valid = '+0123456789 -()';
                for(let i = 0; i < v.length; ++i) {
                    if (!valid.includes(v[i])) {
                        return false;
                    }
                }
                
                return true;
            }
        });

        return this;
    }

    isBool() {
        this.checks.push({
            function: function(value) {
                return typeof value == 'boolean'
            }
        });

        return this;
    }

    has(property) {
        this.checks.push({
            function: function(value, param) {
                return value[param] !== undefined;
            },
            parameter: property
        }); 

        return this;
    }

    notEmpty() {
        this.checks.push({
            function: function(value) {
                if (!value) return false;
                
                if (Array.isArray(value)) {
                    if (value.length == 0) return false;
                } else {
                    if (value == '') {
                        return false;
                    }
                }

                return true;
            }
        }); 

        return this;
    }

    validate(value = undefined) {

        if (value !== undefined) {
            this.value = value;
        }

        for(let check of this.checks) {

            let func = check['function'];
            let param = check['parameter'];

            let valid = func(this.value, param);

            if (!valid) {
                return false;
            }

        }

        return true;

    }

}