/* Intelligent Web NameSpace */
var IW = IW || {};

/**
 * Password validator logic
 */
(function(IW) {

    var secondsInADay = 86400;

    function PasswordValidator() {
    }

    /**
     * How long a password can be expected to last
     */
    PasswordValidator.prototype.passwordLifeTimeInDays = 365;

    /**
     * An estimate of how many attempts could be made per second to guess a password
     */
    PasswordValidator.prototype.passwordAttemptsPerSecond = 500;

    /**
     * An array of regular expressions to match against the password. Each is associated
     * with the number of unique characters that each expression can match.
     * @param password
     */
    PasswordValidator.prototype.expressions = [
        {
            regex : /[A-Z]+/,
            uniqueChars : 26
        },
        {
            regex : /[a-z]+/,
            uniqueChars : 26
        },
        {
            regex : /[0-9]+/,
            uniqueChars : 10
        },
        {
            regex : /[!\?.;,\\@$£#*()%~<>{}\[\]]+/,
            uniqueChars : 17
        }
    ];

    /**
     * Checks the supplied password
     * @param {String} password
     * @return The predicted lifetime of the password, as a percentage of the defined password lifetime.
     */
    PasswordValidator.prototype.checkPassword = function(password) {
  if (password == null) password="0"
        var
                expressions = this.expressions,
                i,
                l = expressions.length,
                expression,
                possibilitiesPerLetterInPassword = 0;

        for (i = 0; i < l; i++) {

            expression = expressions[i];

            if (expression.regex.exec(password)) {
                possibilitiesPerLetterInPassword += expression.uniqueChars;
            }

        }

        var
                totalCombinations = Math.pow(possibilitiesPerLetterInPassword, password.length),
            // how long, on average, it would take to crack this (@ 200 attempts per second)
                crackTime = ((totalCombinations / this.passwordAttemptsPerSecond) / 2) / secondsInADay,
            // how close is the time to the projected time?
                percentage = crackTime / this.passwordLifeTimeInDays;

        return Math.min(Math.max(password.length * 5, percentage * 100), 100);

    };

    IW.PasswordValidator = new PasswordValidator();

})(IW);

/**
 * jQuery plugin which allows you to add password validation to any
 * form element.
 */
(function(IW, jQuery) {

    function updatePassword() {

        var
                percentage = IW.PasswordValidator.checkPassword(this.val()),
                progressBar = this.parent().find(".passwordStrengthBar div");

        progressBar
                .removeClass("strong medium weak useless")
                .stop()
                .animate({"width": percentage + "%"});

        if (percentage > 90) {
            progressBar.addClass("strong");
        } else if (percentage > 50) {
            progressBar.addClass("medium")
        } else if (percentage > 10) {
            progressBar.addClass("weak");
        } else {
            progressBar.addClass("useless");
        }
    }

    jQuery.fn.passwordValidate = function() {

        this
                .bind('keyup', jQuery.proxy(updatePassword, this))
                .after("<div class='passwordStrengthBar'>" +
                "<div></div>" +
                "</div>");

        updatePassword.apply(this);

        return this; // for chaining

    }

})(IW, jQuery);

/* Have all the password elements on the page validate */
jQuery("input[type='password']").passwordValidate();
