/**
 * 0 Step Checkout frontend controller
 *
 * @category    FI
 * @package     FI_Checkout
 * @author      Sergiy Stotskiy <sergiy.stotskiy@freaksidea.com>
 * @copyright   Copyright (c) 2012 Sergiy Stotskiy (http://freaksidea.com)
 */

/**
 * Storage plugin
 * Provides a simple interface for storing data such as user preferences.
 * Storage is useful for saving and retreiving data from the user's browser.
 * For newer browsers, localStorage is used.
 * If localStorage isn't supported, then cookies are used instead.
 * Retrievable data is limited to the same domain as this file.
 *
 * Usage:
 * This plugin extends jQuery by adding itself as a static method.
 * $.Storage - is the class name, which represents the user's data store, whether it's cookies or local storage.
 *             <code>if ($.Storage)</code> will tell you if the plugin is loaded.
 * $.Storage.set("name", "value") - Stores a named value in the data store.
 * $.Storage.set({"name1":"value1", "name2":"value2", etc}) - Stores multiple name/value pairs in the data store.
 * $.Storage.get("name") - Retrieves the value of the given name from the data store.
 * $.Storage.remove("name") - Permanently deletes the name/value pair from the data store.
 *
 * @author Dave Schindler
 * @modified by Sergiy Stotskiy
 *
 * Distributed under the MIT License
 *
 * Copyright (c) 2010 Dave Schindler
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
(function($) {
    // Private data
    var isLS=typeof window.localStorage!=='undefined';
    // Private functions
    function wls(n,v){var c;if(typeof n==="string"&&typeof v==="string"){localStorage[n]=v;return true;}else if(typeof n==="object"&&typeof v==="undefined"){for(c in n){if(n.hasOwnProperty(c)){localStorage[c]=n[c];}}return true;}return false;}
    function wc(n,v){var dt,e,c;dt=new Date();dt.setTime(dt.getTime()+31536000000);e="; expires="+dt.toGMTString();if(typeof n==="string"&&typeof v==="string"){document.cookie=n+"="+v+e+"; path=/";return true;}else if(typeof n==="object"&&typeof v==="undefined"){for(c in n) {if(n.hasOwnProperty(c)){document.cookie=c+"="+n[c]+e+"; path=/";}}return true;}return false;}
    function rls(n){return localStorage[n];}
    function rc(n){var nn, ca, i, c;nn=n+"=";ca=document.cookie.split(';');for(i=0;i<ca.length;i++){c=ca[i];while(c.charAt(0)===' '){c=c.substring(1,c.length);}if(c.indexOf(nn)===0){return c.substring(nn.length,c.length);}}return null;}
    function dls(n){ try { delete localStorage[n] } catch (e) { localStorage[n] = ''}}
    function dc(n){return wc(n,"",-1)}

    /**
    * Public API
    * $.Storage - Represents the user's data store, whether it's cookies or local storage.
    * $.Storage.set("name", "value") - Stores a named value in the data store.
    * $.Storage.set({"name1":"value1", "name2":"value2", etc}) - Stores multiple name/value pairs in the data store.
    * $.Storage.get("name") - Retrieves the value of the given name from the data store.
    * $.Storage.remove("name") - Permanently deletes the name/value pair from the data store.
    */
    $.Storage = {
        set: isLS ? wls : wc,
        get: isLS ? rls : rc,
        remove: isLS ? dls :dc
    };
})(Object);
Element.prototype.triggerEvent = function(eventName) {
    if (document.createEvent) {
        var e = document.createEvent('HTMLEvents');
        e.initEvent(eventName, true, true);

        return this.dispatchEvent(e);
    }

    if (document.createEventObject) {
        var e = document.createEventObject();
        return this.fireEvent('on' + eventName, e);
    }
};

var FreaksTabs = Class.create();
FreaksTabs.prototype = {
    initialize: function(tabsBlock, params) {
        this.onChangeTab = null;
        this.forms  = {};
        this.params = Object.extend({
            currentClassName: 'current',
            contentTagName:   'div'
        }, params || {});

        if (!tabsBlock) {
            return false;
        }
        this.tabsBlock = $(tabsBlock);

        var $this = this;
        this.tabsBlock.observe('click', function(e) {
            var that = Event.element(e);
            if (that.nodeName.toLowerCase() != 'a') {
                return true;
            }

            $this.setCurrentTab(that.href.substr(that.href.lastIndexOf('#') + 1));
            Event.stop(e);
        });
    },
    setCurrentTab: function(id) {
        var tab = this.getTab(id);

        if (tab.button && tab.content && tab.button != this.getCurrentTab()) {
            var curTab = {
                button:  this.getCurrentTab(),
                content: this.getCurrentContent()
            };
            curTab.content.hide();
            curTab.button.removeClassName(this.params.currentClassName);

            $(tab.button.parentNode).addClassName(this.params.currentClassName);
            $(tab.content).show();

            if (this.onChangeTab && this.onChangeTab.call) {
                this.onChangeTab({
                    prev:    curTab,
                    current: tab
                });
            }
        }
    },
    getCurrentTab: function() {
        if (this.tabsBlock) {
            return this.tabsBlock.select('.' + this.params.currentClassName)[0];
        }
        return null;
    },
    getCurrentContent: function() {
        var btn = this.getCurrentTab().firstDescendant(),
            id  = btn.href.substr(btn.href.lastIndexOf('#') + 1);

        return $(this.tabsBlock.parentNode).select(this.params.contentTagName + '.' + id)[0];
    },
    getTab: function(id) {
        var tabContent = $(this.tabsBlock.parentNode).select(this.params.contentTagName + '.' + id)[0],
            tabBtn     = '';

        this.tabsBlock.select('a').each(function(e) {
            var btnId = e.href.substr(e.href.lastIndexOf('#') + 1);
            if (btnId == id) {
                tabBtn = e;
                return false;
            }
        });

        return {button: tabBtn, content: tabContent};
    },
    setTabForm:function (id, form) {
        this.forms[id] = form;
        return this;
    },
    getTabForm: function(id) {
        return this.forms[id];
    },
    getTabForms: function(){
        return this.forms;
    },
    getCurrentForm: function() {
        var tab = this.getCurrentTab();
        if (!tab) {
            return null;
        }
        var btn = tab.firstDescendant(),
            id = btn.href.substr(btn.href.lastIndexOf('#') + 1);

        return this.forms[id];
    }
};

RestorableForm = Class.create();
RestorableForm.Storage = Object.Storage;
RestorableForm.prototype = new VarienForm();
RestorableForm.prototype.initialize = (function(super_initialize) {
    return function(formId, firstFieldFocus) {
        super_initialize.call(this, formId, firstFieldFocus);
        this.formId = formId;
        if (this.form) {
            this.restoreValues();
            this.form.observe('submit', this.submit.bindAsEventListener(this));

            this.embededForms    = {};
            this.hasEmbededForms = false;
            this.responsePopup   = null;
        }
    };
})(VarienForm.prototype.initialize);

RestorableForm.prototype.getKey = function(name) {
    return this.formId + '-' + name;
};

RestorableForm.prototype.clearValues = function(names){
    var i = names.length, key = '';
    while (i--) {
        key = this.getKey(names[i]);
        if (RestorableForm.Storage.get(key)) {
            RestorableForm.Storage.remove(key);
        }
    }
    return this;
};

RestorableForm.prototype.restoreValues = function(){
    var self = this, fn = function(e) {
        var el = $(this), n = el && el.nodeName && el.nodeName.toLowerCase();
        if (n != 'input' && n != 'select' && n != 'textarea') {
            el = el.select('input')[0];
        }
        RestorableForm.Storage.set(self.getKey(el.name), el.value);
    };
    Form.getElements(this.form).each(function(e) {
        if(e.hasClassName('non-storable')) {
            return true;
        }

        var v = RestorableForm.Storage.get(self.getKey(e.name));
        if (v) {
            if (e.type == 'radio' || e.type == 'checkbox') {
                if (e.value == v) {
                    e.checked = true;
                }
            } else {
                e.value = v;
            }
        }

        if (e.type == 'radio' || e.type == 'checkbox') {
            e.up().observe('click', fn);
        } else {
            e.observe('change', fn);
        }
    });
    return this;
};

RestorableForm.prototype.submit = function(async, event) {
    var isValid = this.isValid();
    if(isValid) {
        var self = this, mainForm = this.form, clearStorage = function(el) {
            var key = self.getKey(el.name);
            if (RestorableForm.Storage.get(key)) {
                RestorableForm.Storage.remove(key);
            }
        }, addElements = function(el){
            if (el.disabled || (el.type == 'radio' || el.type == 'checkbox') && !el.checked) {
                return;
            }
            var inp = mainForm[el.name];
            if (!inp) {
                inp = new Element('input');
                inp.type = 'hidden';
                inp.name = el.name;
                mainForm.appendChild(inp);
            }
            inp.value = el.value;
        };
        Form.getElements(this.form).each(clearStorage);
        if (this.hasEmbededForms) {
            for (var formId in this.embededForms) {
                var formObject = this.embededForms[formId];
                Form.getElements(formObject.form).each(clearStorage).each(addElements);
            }
        }
        if (async) {
            this.request();
        } else {
            this.form.submit();
        }
    }
    return isValid;
};

RestorableForm.prototype.request = function() {
    return new Ajax.Request(this.form.getAttribute('action'), {
        method: this.form.getAttribute('method'),
        parameters: Form.serialize(this.form, true),
        onComplete: (function(tr) {
            try {
                var response = tr.responseText.evalJSON();
            } catch (e) {
                return;
            }

            if (response.redirect) {
                location.href = response.redirect;
            } else if (response.error_message) {
                var msg = this.form.previous('ul.messages');
                if (msg) {
                    msg.up().removeChild(msg);
                }
                this.form.insert({before: response.error_message});
                this.form.up(1).scrollTo();
            }
            this.form.fire('form:has_response', response);
        }).bind(this)
    });
};

RestorableForm.prototype.isValid = function() {
    var isValid = this.validator && this.validator.validate();

    if (isValid && this.hasEmbededForms) {
        for (var formId in this.embededForms) {
            var form = this.embededForms[formId];
            isValid = isValid && form.validator && form.validator.validate();

            if (!isValid) {
                break;
            }
        }
    }
    return isValid;
};

RestorableForm.prototype.addEmbededForm = function(form) {
    if (form.form) {
        this.hasEmbededForms = true;
        this.embededForms[form.form.id] = form;
    }

    return this;
};
RestorableForm.prototype.getEmbededForms = function() {
    return this.embededForms;
};

RestorableForm.prototype.getEmbededForm = function(formId) {
    return this.embededForms[formId];
};

var fiSelectBox = Class.create({
    initialize: function(element) {
        this.element = $(element);

        if (this.element) {
            this.clearInputValueIfNotInList();
            this.render();
            this.addSelectObserver(true);
        }
    },
    clearInputValueIfNotInList: function () {
        var text = $(this.element).previous('input[type="text"]'),
            value = text.value.strip().replace(/"/g, '\\"');
        if (this.element.select('option').length && !this.element.select('option:contains("' + value +'")').length) {
            text.value = '';
        }
    },
    addSelectObserver: function(isFirstTime){
        var fn = function(e, isFirstTime) {
            var text = $(this).previous('input[type="text"]');
            if (this.selectedIndex >= 0 && this.value != 0) {
                text.value = this.options[this.selectedIndex].innerHTML;
            } else if (!isFirstTime) {
                text.value = '';
            }

            if (!isFirstTime) {
                text.fire('fiSelectBox:change', {event: e});
            }
        };
        this.element.observe('change', fn);
        fn.call(this.element, null, isFirstTime);
        return this;
    },
    render: function(html) {
        if (html) {
            var oldSelect = this.element;
            oldSelect.insert({after: html});
            this.element = oldSelect.next();
            oldSelect.up().removeChild(oldSelect);
            this.addSelectObserver();
        }
        if (!this.element.getElementsByTagName('option').length) {
            this.element.disabled = true;
            this.element.hide();
        } else {
            this.element.disabled = false;
            this.element.show();
        }
        return this;
    },
    addChangeListener: function(fn) {
        if (this.element) {
            var text = this.element.previous('input[type="text"]');
            // IE8 crash fix
            text.observe('fiSelectBox:change', fn.bind(this));
        }
        return this;
    },
    dependsOn: function(element, paramName, loadingClass) {
        paramName    = paramName || 'value';
        loadingClass = loadingClass || 'input-loading';

        var params = {}, self = this;
        element && element.observe('change', function(e){
            var input = self.element.previous('input[type="text"]');

            params[paramName] = this.value;
            input.addClassName(loadingClass);
            input.value = '';
            input.disabled = true;
            new Ajax.Request(this.parentNode.getAttribute('data-action'), {
                method: 'get',
                parameters: params,
                onComplete: function(tr){
                    input.removeClassName(loadingClass)
                    input.disabled = false;
                    self.render(tr.responseText);
                }
            })
        });
        return this;
    }
});

var FreaksAutocompleter = Class.create(Ajax.Autocompleter, {
    startIndicator: function() {
        if(this.options.indicator) this.element.addClassName(this.options.indicator)
    },
    stopIndicator: function() {
        if(this.options.indicator) this.element.removeClassName(this.options.indicator)
    },
    showResults: function() {
        this.hasFocus = true;
        this.changed  = false;
        this.startIndicator();

        this.options.parameters = encodeURIComponent(this.options.paramName) + '=' +
            encodeURIComponent(this.element.value);

        if(this.options.defaultParams)
            this.options.parameters += '&' + this.options.defaultParams;

        new Ajax.Request(this.url, this.options);
        setTimeout(this.stopIndicator.bind(this), 1500);
    },
    addChangeListener: function(fn) {
        Event.observe(this.element, 'change', (function(e){
            // multiple requests protection
            if (this.changeTimeout) {
                clearTimeout(this.changeTimeout);
            }
            var self = this;
            this.changeTimeout = setTimeout(function(){
                fn.call(self, e);
            }, 100);
        }).bindAsEventListener(this));
        return this;
    },
    addShowResults: function(selector) {
        Event.observe(this.element.next(selector), 'click', (function(e){
            Event.stop(e);
            this.showResults();
        }).bindAsEventListener(this));
        return this;
    },
    updateElement: function(selectedElement) {
        var value = '';
        if (this.options.select) {
            var nodes = $(selectedElement).select('.' + this.options.select) || [];
            if(nodes.length>0) value = Element.collectTextNodes(nodes[0], this.options.select);
        } else {
            value = Element.collectTextNodesIgnoreClass(selectedElement, 'informal');
        }

        var ex = this.element.value.split(/\s*,\s*/);
        if (value != ex[ex.length - 2]) {
            ex[ex.length - 1] = value;
        }
        this.element.value = ex.join(', ');
        this.oldElementValue = this.element.value;
        this.element.focus();
        this.element.triggerEvent('change');
    }
});

var fiCheckoutViewModel = Class.create({
    initialize: function(sections) {
        this.updateUrl = '';
        this.sections  = {};
        this.requests  = {};
        this.createSections(sections);
    },
    isActive: function() {
        return this.getUrl() != null;
    },
    createSections: function (sections) {
        for (var i in sections) {
            if (sections[i]) {
                this.sections[i] = sections[i];
            }
        }
        return this;
    },
    findUrl: function() {
        var url = '';
        for (var code in this.sections) {
            var section = this.sections[code];
            if (url = section.getAttribute('data-action')) {
                break;
            }
        }
        return url;
    },
    getUrl: function(){
        if (this.updateUrl) {
            return this.updateUrl;
        }

        return this.updateUrl = this.findUrl();
    },
    updateView: function(sectionsHtml) {
        for (var code in this.sections) {
            var html = sectionsHtml[code], section = this.sections[code];
            if (html) {
                if (section.nodeName.toLowerCase() == 'table') {
                    section.insert({after: html});
                    this.sections[code] = section.next();
                    section.up().removeChild(section);
                    section = this.sections[code];
                } else {
                    section.update(html);
                }
            }
            section.up().removeClassName('loading');
            section.setStyle({height:'', visibility:'visible'});
        }
    },
    ajaxCallback: function(tr) {
        var code = tr.responseText;
        try {
            code = code.evalJSON();
            this.updateView(code);
            $(document).fire('fiCheckout:updated', {viewModel: this});
        } catch (e) {
        }
    },
    waiting: function(code){
        var codes = [];
        if (code) {
            var section = this.sections[code];
            section.setStyle({height: section.getHeight() + 'px', visibility:'hidden'});
            $(section.parentNode).addClassName('loading');
            codes.push(code);
        } else {
            for (var code in this.sections) {
                var section = this.sections[code];
                section.setStyle({height: section.getHeight() + 'px', visibility:'hidden'});
                $(section.parentNode).addClassName('loading');
                codes.push(code);
            }
        }
        return codes;
    },
    request: function(params, onComplete) {
        return new Ajax.Request(this.getUrl(), {
            method: 'post',
            parameters: params,
            onComplete: onComplete ? onComplete.bind(this) : this.ajaxCallback.bind(this),
            onFailure: function() {
                location.href = location.href;
            }
        });
    },
    sendOnceFor: function (code, params) {
        if (this.requests[code]) {
            this.requests[code].transport.abort();
        }
        this.requests[code] = this.request(params, function () {
            delete this.requests[code];
            this.ajaxCallback.apply(this, arguments);
        });
        return this.requests[code];
    },
    getSection: function(code) {
        return this.sections[code] || null;
    },
    updateSection: function(code, params) {
        var names = code.split(','), i = names.length;

        while (i--) {
          this.waiting(names[i]);
        }

        params = params || {};
        params.type = code;

        return this.sendOnceFor(code, params);
    },
    updateAll: function(params) {
        var codes = this.waiting();
        params = params || {};
        params.type = codes.join(',');
        return this.sendOnceFor(params.type, params);
    },
    processing: function(id) {
        var btn = this.disableButton(id);
        if (btn) {
            btn.previous('.process').show();
        }
    },
    stopProcessing: function(id) {
        var btn = $(id);
        if (btn) {
            btn.previous('.process').hide();
        }
    },
    disableButton: function(id) {
        var btn = $(id);
        if (btn) {
            btn.addClassName('disabled');
            btn.disabled = true;
            btn.up('ul').select('li').each(function(el) {
                if (el != btn.parentNode) {
                    el.setStyle({visibility: 'hidden'});
                }
            })
        }
        return btn;
    },
    enableButton: function(id) {
        var btn = $(id);
        if (btn) {
            btn.removeClassName('disabled');
            btn.disabled = false;
            btn.up('ul').select('li').each(function(el) {
                if (el != btn.parentNode) {
                    el.setStyle({visibility: 'visible'});
                }
            })
        }
        return btn;
    },
    getChangeListener: function() {
        var self = this;
        return function() {
            var fields = ['country_id', 'region', 'postcode', 'city'], i = fields.length,
                element = this.element || this, params = {};

            if (element.value.strip() && element.value != '0') {
                params[element.name] = element.value;
                while (i--) {
                    var el = element.form['user[address][' + fields[i] + ']'];
                    if (el && el.value) {
                        params[el.name] = el.value;
                    }
                }
                self.updateAll(params);
                self.disableButton('place-order');
            }
        };
    }
});

fiCheckoutViewModel.isElement = function(target, type) {
    var nodeName = target && target.nodeName.toLowerCase();

    return nodeName == 'input' && target.type == type
};

var fiPaymentViewModel = Class.create({
    initialize: function(element) {
        this.element = element;
        this.currentMethod = '';
        this.restore();
    },
    getCurrentMethod: function(){
        return this.currentMethod;
    },
    getCurrentMethodElement: function() {
        return this.element && this.element.select('input[type="radio"]:checked')[0];
    },
    findCurrentMethod: function(){
        var method = this.getCurrentMethodElement();
        return method ? method.value : '';
    },
    getAdditionalForm: function(){
        return $('payment_form_' + this.currentMethod);
    },
    enableFormElements: function(form, flag){
        var elms = Form.getElements(form), i = elms.length;
        while (i--) {
            elms[i].disabled = !flag;
        }
        form[flag ? 'show' : 'hide']();
        return this;
    },
    showAdditionalForm: function(flag) {
        var info = this.getAdditionalForm();

        if (info) {
            this.enableFormElements(info, flag);
        }
        return this;
    },
    setMethod: function(method) {
        this.currentMethod = method;
        var info = this.getAdditionalForm();
        $(info || document.body).fire('payment-method:switched', {method_code : method});
        return this;
    },
    switchMethod: function(method) {
        this.showAdditionalForm(false);
        this.setMethod(method);
        this.showAdditionalForm(true);
    },
    restore: function() {
        var self = this;
        if (this.element) {
            this.switchMethod(this.findCurrentMethod());

            this.element.select('.form-list').each(function(el){
                self.enableFormElements(el, el.offsetWidth != 0);
            });
        }
        return this;
    }
});

var fiCentinelViewModel = Class.create({
    initialize: function(block) {
        this.element = block;
        this.isValid = false;

        if (this.element) {
            var close = this.element.up().select('a.popup-close')[0],
                self  = this;
            close.observe('click', function(e){
                $(this).up().hide();
                Event.stop(e);
                self.element.fire('fiCentinel:cancel');
            });
        }
    },
    validate: function(url, params) {
        var self = this;
        self.element.setStyle({visibility: 'hidden'});
        self.element.up().addClassName('loading');
        self.element.up().setStyle({display:'block'});

        return new Ajax.Request(url, {
            method: 'post',
            parameters: params,
            onComplete: function(tr) {
                try {
                    var response = tr.responseText.evalJSON();
                } catch (e) {
                    return;
                }

                self.element.update(response.html);
                self.element.setStyle({visibility: 'visible'});
                self.element.up().removeClassName('loading');
                if (response.url) {
                    self.process(response.url);
                } else {
                    self.success();
                }
            }
        });
    },
    idle: function(){
        this.element.up().hide();
    },
    process: function(url) {
        var iframe = this.element.select('iframe')[0];
        iframe.src = url;
        return this;
    },
    moveElement: function(x, y) {
        if (this.element) {
            this.element.up().setStyle({
                top: x  + 'px',
                left: y + 'px'
            });
        }
        return this;
    },
    isSuccessfull: function() {
        return this.isValid;
    },
    resetValidation: function () {
        this.isValid = false;
        return this;
    },
    success: function() {
        if (this.element) {
            setTimeout((function(){
                this.element.up().hide();
            }).bind(this), 2000);
            this.isValid = true;
            this.element.fire('fiCentinel:success');
        }
    }
});

var fiPage = Class.create({
    addFieldPopup: function(form, fieldName) {
        if (!form.form) {
            return false;
        }
        var field = $(form.form[fieldName]);
        if (!field || !field.next('.popup')) {
            return false;
        }
        field.observe('focus', function(){
            $(this).next('.popup').show();
        });
        field.observe('blur', function(){
            $(this).next('.popup').hide();
        });
        return field;
    },
    createTabs: function(element, tabForms, embededForms) {
        var tabs = new FreaksTabs(element), tab = '';

        tabs.onChangeTab = function(e){
            if (e.prev.content.hasClassName('new-user')
                && !e.current.content.hasClassName('new-user')
            ) {
                e.prev.content.show().addClassName('hidden-tab');
                e.prev.content.next('div.overlay').setStyle({display:'block'});
            } else {
                e.current.content.removeClassName('hidden-tab');
                e.current.content.next('div.overlay').hide();
            }
            var c = $(e.prev.content), ul = c.select('ul.messages')[0];
            ul && ul.hide();
        };

        for (var key in tabForms) {
            tabs.setTabForm(key, new RestorableForm(tabForms[key]));
        }

        var form = tabs.getCurrentForm();
        if (form && embededForms) {
            for (var formId in embededForms) {
                form.addEmbededForm(embededForms[formId]);
            }
        }

        if (tab = (location.hash || ' ').substr(1)) {
            tabs.setCurrentTab(tab);
        }

        return tabs;
    },
    createDiscount: function(id) {
        var discountForm = new VarienForm(id);
        discountForm.submit = function (isRemove) {
            if (isRemove) {
                $(this.form['coupon_code']).removeClassName('required-entry');
                $(this.form['remove']).value = "1";
            } else {
                $(this.form['coupon_code']).addClassName('required-entry');
                $(this.form['remove']).value = "0";
            }
            return VarienForm.prototype.submit.call(this);
        };
        if (discountForm.form) {
            discountForm.form.select('button').each(function(button){
                button.observe('click', function() {
                    discountForm.submit(this.name == 'cancel');
                });
            })
        }
    },
    createForms: function(ids) {
        var i = ids.length, forms = {};
        while (i--) {
            forms[ids[i]] = new RestorableForm(ids[i]);
        }

        return forms;
    },
    clearStorage: function(forms, values) {
        var formId = '', form = '';

        for (formId in forms) {
            form = forms[formId];
            if (form.clearValues && !form.form) {
                form.clearValues(values);
            }
        }
    },
    getLocationBox: function(div, alternative, form){
        var box = null;
        if (div) {
            box = new FreaksAutocompleter(div.previous('input[id]'), div, div.parentNode.getAttribute('data-action'), {
                paramName: 'location',
                indicator: 'input-loading',
                minChars: 2,
                onShow: function(element, update) {
                    // fix ie position bug
                    Effect.Appear(update,{duration:0.15});
                }
            });
            box.addShowResults('a.select-tip');
        } else if (form) {
            box = new fiSelectBox($(form[alternative.box]));
            box.dependsOn($(form[alternative.dependsOn]), 'country_id', 'input-loading');
        }
        return box;
    },
    observeAddressFields: function(fields, listener, form) {
        if (!form) {
            return;
        }
        var k = fields.length, el = '';

        while (k--) {
            el = $(form['user[address][' + fields[k] + ']']);
            if (el) {
                el.observe('change', listener);
            }
        }
    },
    createCheckout: function(tabs, totals) {
        var self = this;
        var paymentForm = tabs.getCurrentForm().getEmbededForm('payment-form');

        $$('form[name="user_info"]').each(function (form) {
            var shipping = form.select('div.shipping-methods')[0];
            var shippingDependentSections = shipping ? shipping.getAttribute('data-update-sections') : null;
            var viewModel = new fiCheckoutViewModel({
                shipping: shipping,
                payment:  paymentForm.form,
                totals:   totals
            });
            if (shippingDependentSections) {
                shipping.observe('click', function(e){
                    var target = Event.element(e);
                    if (!fiCheckoutViewModel.isElement(target, 'radio')) {
                        return true;
                    }
                    this.updateSection(shippingDependentSections, {
                        shipping_method: target.value
                    });
                    this.disableButton('place-order');
                }.bindAsEventListener(viewModel));
            }

            if (viewModel.isActive()) {
                var listener = viewModel.getChangeListener();
                var box = self.getLocationBox(form.select('div.autocomplete')[0], {
                    box:       'user[address][region_id]',
                    dependsOn: 'user[address][country_id]'
                }, form);

                box && box.addChangeListener(listener);

                self.observeAddressFields(['country_id', 'postcode'], listener, form);
            }

            var paymentDependentSections = paymentForm && paymentForm.form ? paymentForm.form.getAttribute('data-update-sections') : null;
            if (paymentDependentSections) {
                paymentForm.form.observe('click', function (event) {
                    var target = Event.element(event);
                    if (!fiCheckoutViewModel.isElement(target, 'radio')) {
                        return true;
                    }
                    params = {};
                    params[target.name] = target.value;
                    this.updateSection(paymentDependentSections, params);
                    this.disableButton('place-order');
                }.bindAsEventListener(viewModel));
            }
        });

        $(document).observe('fiCheckout:updated', function(event) {
            paymentForm.restoreValues();
            event.memo.viewModel.enableButton('place-order');
        });
    },
    wakeUpPayment: function(pel, cel) {
        var payment  = new fiPaymentViewModel(pel),
            centinel = new fiCentinelViewModel(cel);

        if (payment.element) {
            payment.element.observe('click', function(e) {
                var target = Event.element(e);
                if (!fiCheckoutViewModel.isElement(target, 'radio')) {
                    return true;
                }

                if (payment.getCurrentMethod() != target.value) {
                    payment.switchMethod(target.value);
                    centinel.idle();
                }
            });

            $(document).observe('fiCheckout:updated', function() {
                payment.restore();
            });
        }

        if (centinel.element) {
            var fn = function(){ fiCheckoutViewModel.prototype.enableButton('place-order') };
            centinel.element.observe('fiCentinel:success', fn);
            centinel.element.observe('fiCentinel:cancel', fn);
        }
        return {payment: payment, centinel: centinel};
    },
    send: function(placeOrderButton, tabs, model, content) {
        if (!placeOrderButton) {
            return false;
        }
        var paymentForm = tabs.getCurrentForm() && tabs.getCurrentForm().getEmbededForm('payment-form');
        placeOrderButton.observe('click', function(e) {
            var method = model.payment.getCurrentMethodElement(), result = true,
                url    = '';
            if (method) {
                url = method.getAttribute('data-action');
                if (url && !model.centinel.isSuccessfull()) {
                    var params = Form.serialize(model.payment.getAdditionalForm(), true);
                    params[method.name] = method.value;
                    if (paymentForm.validator.validate()) {
                        model.centinel.validate(url, params);
                        fiCheckoutViewModel.prototype.disableButton(this);
                    }
                    result = false;
                }
            }

            if (result && tabs.getCurrentForm().submit(true)) {
                fiCheckoutViewModel.prototype.processing(this)
            }
        });

        var form = tabs.getCurrentForm();
        if (form.form) {
            form.form.observe('form:has_response', function(event){
                fiCheckoutViewModel.prototype.stopProcessing(placeOrderButton);

                var response = event.memo;
                if (response.error_message) {
                    model.centinel.resetValidation();
                    fiCheckoutViewModel.prototype.enableButton(placeOrderButton);
                }

                if (!response.update_section) {
                    return true;
                }
                content.up().show();
                content.update(response.update_section.html);
                try {
                    response.update_section.html.evalScripts();
                } catch (e) {
                    // workaround fuckin js into templates...
                    content.select('iframe').each(function(el){
                        if (!el.offsetWidth) {
                            el.observe('load', el.show.bind(el));
                        }
                    });
                }
            });
        }

        return true;
    }
});

// Fix for Validator#test method.
// It should call options.onElementValidate callback
Validator.prototype.test = (function (_super) {
    return function (v, elm) {
        var result = _super.apply(this, arguments);
        if (this.options.onElementValidate) {
            this.options.onElementValidate.call(this, result, elm);
        }
        return result;
    }
})(Validator.prototype.test);

Object.extend(Validation.get('validate-state').options, {
    onElementValidate: function (result, element) {
        var text = $(element).previous('input[type="text"]');
        if (result) {
            text.removeClassName('validation-failed').addClassName('validation-passed');
        } else {
            text.addClassName('validation-failed').revemoClassName('validation-passed');
        }
    }
});

$(document).observe('dom:loaded', function(){
    var page  = new fiPage(),
        forms = page.createForms(['payment-form', 'shipping-method-load']),
        loginForm = new VarienForm('checkout-login-form');

    forms['checkout-agreements'] = new VarienForm('checkout-agreements');
    forms['newsletter-form']     = new VarienForm('newsletter-form');

    var tabs  = page.createTabs($$('#checkout-block ul.infos-menus')[0], {
        'new-user':   'new-user-form',
        'registered-user': 'registered-user-form'
    }, forms);

    page.createDiscount('discount-coupon-form');
    page.createCheckout(tabs, $('shopping-cart-totals-table'));

    var content = $$('#payment-popup div.popup-content')[0],
        model = page.wakeUpPayment($('payment-methods'), content);

    page.send($('place-order'), tabs, model, content);

    // backward support for Magento templates
    window.CentinelAuthenticateController = model.centinel;
});