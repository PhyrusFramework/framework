
class Time {

    static formats = {
        'time': 'HH:mm:ss',
        'date': 'DD/MM/YYYY',
        'datetime': 'DD/MM/YYYY HH:mm',
        'sql': 'YYYY-MM-DD HH:mm:ss',
        'string': 'l jS F Y',
        'day of year': 'z'
    }

    static dayNames = {
        0: 'monday',
        1: 'tuesday',
        2: 'wednesday',
        3: 'thursday',
        4: 'friday',
        5: 'saturday',
        6: 'sunday'
    }

    moment;

    static instance(date = null, format = 'YYYY-MM-DD HH:mm:ss') {
        return new Time(date, format);
    }

    constructor(date = null, format = 'YYYY-MM-DD HH:mm:ss') {

        if (date) {
            this.moment = moment(date, format);
        } else {
            this.moment = moment();
        }

    }

    format(format) {
        return this.moment.format(format);
    }

    get(formatName) {
        if (Time.formats[formatName]) {
            return this.moment.format(Time.formats[formatName]);
        }
        return this.moment.format(Time.formats.sql);
    }

    getTimezone() {
        return moment.tz.guess();
    }

    applyTimezone() {
        let tz = moment.tz.guess();
        this.moment = moment.utc(this.get('sql')).tz(tz);
        return this;
    }

    static fromTimestamp(timestamp) {
        let time = new Time();
        time.moment = moment.unix(timestamp);
        return time;
    }

    copy() {
        return new Time(this.get('sql'));
    }

    get timestamp() {
        return this.moment.unix();
    }

    get second() {
        return parseInt(this.format('ss'));
    }

    get minute() {
        return parseInt(this.format('mm'));
    }

    get hour() {
        return parseInt(this.format('HH'));
    }

    get day() {
        return parseInt(this.format('DD'));
    }

    get month() {
        return parseInt(this.format('MM'));
    }

    get year() {
        return parseInt(this.format('YYYY'));
    }

    dayOfWeek(mondayFirst = true) {
        let position = this.moment.day();
        if (mondayFirst) {
            position -= 1;
            if (position < 0) {
                position = 6;
            }
        }

        return {
            position: position,
            day: Time.dayNames[position]
        }
    }

    add(amount, type = 'days') {

        if (amount >= 0) {
            this.moment.add(amount, type);
        } else {
            this.moment.subtract(amount * -1, type);
        }

    }

    setYear(year) {
        this.moment = moment(year + '-MM-DD HH:mm:ss');
    }
    setMonth(month) {
        this.moment = moment('YYYY-'+month+'-DD HH:mm:ss');
    }
    setDay(day) {
        this.moment = moment('YYYY-MM-'+day+' HH:mm:ss');
    }
    setHour(hour) {
        this.moment = moment('YYYY-MM-DD '+hour+':mm:ss');
    }
    setMinute(minute) {
        this.moment = moment('YYYY-MM-DD HH:'+minute+':ss');
    }
    setSecond(second) {
        this.moment = moment('-MM-DD HH:mm:' + second);
    }

    toNow() {
        return new TimeInterval(this);
    }

    since(time) {
        return new TimeInterval(time, this);
    }

    until(time) {
        return new TimeInterval(this, time);
    }

    isBefore(other) {
        let diff = new TimeInterval(this, other);
        return diff.seconds > 0;
    }

    isAfter(other) {
        let diff = new TimeInterval(this, other);
        return diff.seconds < 0;
    }

}

class TimeInterval {

    start;
    end;
    ms;

    constructor(since, until = null) {
        this.start = since;
        this.end = until ? until : new Time();
        this.ms = this.end.diff(this.start);
    }

    invert() {
        this.ms = this.start.diff(this.end);
        let aux = this.start;
        this.start = this.end;
        this.end = aux;
    }

    get seconds() {
        return this.ms / 1000;
    }

    get minutes() {
        return this.ms / 1000 / 60;
    }

    get hours() {
        return this.ms / 1000 / 3600;
    }

    get days() {
        return this.ms / 1000 / 3600 / 24;
    }

    get weeks() {
        return this.ms / 1000 / 3600 / 24 / 7;
    }

    get months() {
        return this.ms / 1000 / 3600 / 24 / 30;
    }

    get years() {
        return this.ms / 1000 / 3600 / 24 / 365;
    }

    total() {

        let rest = this.ms;

        let years = Math.floor(rest / 1000 / 3600 / 24 / 365);
        rest -= years*365*24*3600000;

        let months = Math.floor(rest / 1000 / 3600 / 24 / 30);
        rest -= months * 3600000*24*30;

        let days = Math.floor(rest / 1000 / 3600 / 24);
        rest -= days * 3600000*24;

        let hours = Math.floor(rest / 1000 / 3600 );
        rest -= hours * 3600000;

        let minutes = Math.floor(rest / 1000 / 60);
        rest -= minutes * 1000 * 60;

        let seconds = Math.floor(rest / 1000);
        
        return {
            years: years,
            months: months,
            days: days,
            hours: hours,
            minutes: minutes,
            seconds: seconds
        }
    }

}