var popupStatus = 0;

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.triage = {
    attach: function (context, settings) {
      triage_clicks();
      var use_pop = drupalSettings.triage.popup.use_pop;
      var numskips = drupalSettings.triage.popup.numskips;
      var nowpath = $(location).attr('href').substring($(location).attr('href').lastIndexOf('/') + 1);
      $.cookie('triage_curr_path', "", {path: '/'});
      var exist = $('#triage-popup').length;
      if (exist == 0) {
        $("body").append("<div id='triage-popup' ></div>");
      }
      exist = $('#bdslegalpopup').length;
      if (exist == 0) {
        $("body").append("<div id='bdslegalpopup'></div>");
      }
      if (!Drupal.behaviors.triage.click_set) {
        if (drupalSettings.triage.popup.use_pop && typeof drupalSettings.triage.popup.use_popup != 'undefined') {
          if (drupalSettings.triage.popup.msg) {
            var useit = $.cookie('triage_use_pop');
            if (typeof useit == "undefined") {
              useit = true;
            }
            var triage_complete = $.cookie('triage_finished');
            if (typeof triage_complete === "undefined") {
              triage_complete = 0;
            }
            var triage_skips = $.cookie('triage_skips');
            if (typeof triage_skips === "undefined") {
              triage_skips = 0;
            }
            if (triage_complete == 1) {
              ++triage_skips;
              useit = false;
              if (triage_skips > numskips) {
                triage_skips = 0;
                useit = true;
              }
              $.cookie('triage_skips', triage_skips, {path: '/'});
            }
            thispath = $.cookie('triage_curr_path', {path: '/'});
            if (useit === true) {
              $.cookie('triage_finished',0, {path: '/'});
              $.cookie('triage_skips', 0, {path: '/'});
              if (thispath !== nowpath) {
                $.cookie('triage_curr_path', nowpath, {path: '/'});
                $('#bdslegalpopup').html(drupalSettings.triage.popup.msg);
                bdslegalloadPopup('bdslegalpopup');
              }
            }
          }
        }
        //Your code.
        Drupal.behaviors.triage.click_set = true;
      }
      $('fieldset.collapsed legend').siblings().hide();
      if ($('body.path-triage').length) {
        if (!$('.triage-master').length) {
          $('.triage-main-body').wrap("<div class='triage-master'>");
        }
        $(window).once("triage").bind("beforeunload", function () {
          var triage_path = window.location.protocol + "//" + window.location.host;
          var url = triage_path + drupalSettings.path.baseUrl + "triage_write_hist";
          console.log(url);
          $.ajax({
            async: false, cache: false, url: url,
          });
        });
        $("input[type=submit]").click(function (e) {
          $(window).unbind("beforeunload");
        });
        $(".triage-master").find("button").first().focus();
        var triage_path = window.location.protocol + "//" + window.location.host;
        var lang = drupalSettings.triage.my_triage.lang;
        var defa_lang = drupalSettings.triage.my_triage.default_lang;
        if (lang == defa_lang) {
          lang = '';
        }
        else {
          lang = lang + "/";
        }
        triage_path = triage_path + drupalSettings.path.baseUrl + lang + "triage/" + drupalSettings.triage.my_triage.vocab + "/";
        if (!top.location === triage_path) {
          top.location = triage_path;
        }
      }
      if ($('body.path-triage-actions-admin').length) {
        $(".triage-admin-top > a").once('triage').click(function (event) {
          event.preventDefault();
          if ($(this).parent().hasClass("collapsed")) {
            $(this).parent().removeClass("collapsed");
            $(this).parent().addClass("expanded")
          }
          else {
            $(this).parent().addClass("collapsed")
            $(this).parent().removeClass("expanded");
          }
        });
      }
      if ($('body.path-triage').length && $('#triage-zip-form .input-group:visible') && $('#triage-zip-form .mobile-zip').length < 1 && $(window).width() < 600) {
        $('#triage-zip-form .input-group').wrap("<div class='mobile-zip'></div>");
        $('#triage-zip-form .mobile-zip').append($('.triage-group-submit.next-144').detach());
        $('#triage-zip-form .mobile-zip .triage-group-submit').css('position', 'unset');
        $('#triage-zip-form .mobile-zip .triage-group-submit').css('padding-left', '30px');
        triage_clicks();
      }
      $('fieldset.collapsed legend').once('triage').click(function () {
        $(this).parents('fieldset').toggleClass('collapsed', 'expanded');
        $(this).siblings().toggle();
      });

      if ($('body.path-triage-actions-process').length) {


        $("button.triage-print-button").once('triage').click(function () {
          var info = $(".triage-region.ta-main-panel").html();
          info = "<h2>Help for Your Legal Problem</h2>" + info;
          $('.dialog-off-canvas-main-canvas').wrap("<div id='page-wrapper'></div>");
          var hold = $("body .dialog-off-canvas-main-canvas").detach();
          $('#page-wrapper').html(info);
          window.print();
          $('#page-wrapper').html(hold);
        });


        $('.triage-again').addClass('clickable-div');
        var wrap = $('.article-wrapper h4');
        if (!wrap.hasClass('expanded')) {
          wrap.addClass('expanded');
        }
        wrap = $('.form-wrapper h4');
        if (!wrap.hasClass('expanded')) {
          wrap.addClass('expanded');
        }
        wrap = $('.classroom-wrapper h4');
        if (!wrap.hasClass('expanded')) {
          wrap.addClass('expanded');
        }
        wrap = $('.legal-help-wrapper h4');
        if (!wrap.hasClass('expanded')) {
          wrap.addClass('expanded');
        }
        if ($('.legal-help-wrapper div').length == 0) {
          $('.legal-help-wrapper').hide();
        }
        wrap = $('.video-wrapper h4');
        if (!wrap.hasClass('expanded')) {
          wrap.addClass('expanded');
        }
        $('.article-wrapper h4,.form-wrapper h4,.classroom-wrapper h4,.legal-help-wrapper h4, .video-wrapper h4').once('triage').click(function () {
          $(this).toggleClass('collapsed');
          if ($(this).hasClass('collapsed')) {
            $(this).parent().children('div').hide('fast');
          }
          else {
            $(this).parent().children('div').show('fast');
          }
        })
      }

      //triage_movebtns();
      function triage_moveon(item) {
        $('div.triagetip').remove();
        if (!$('.triage-master').length) {
          $('.triage-main-body').wrap("<div class='triage-master'>");
        }
        var triage_path = window.location.protocol + "//" + window.location.host;
        var lang = drupalSettings.triage.my_triage.lang;
        var defa_lang = drupalSettings.triage.my_triage.default_lang;
        if (lang == defa_lang) {
          lang = '';
        }
        else {
          lang = lang + "/";
        }
        triage_path = triage_path + drupalSettings.path.baseUrl + lang + "triage/" + drupalSettings.triage.my_triage.vocab + "/";
        var nohelp = item.hasClass('tr-help');
        if (nohelp) {
          item.removeClass('tr-help');
        }
        var thisclass = item.attr('class');
        var tid = thisclass.replace('triage-row trow-', '');
        var len = drupalSettings.triage.my_triage.tid.length;
        drupalSettings.triage.my_triage.tid[len] = tid;
        var url = triage_path + tid + "/1/-1";
        //alert(url);
        triage_getdata(url);
        triage_clicks();
        // var offset = drupalSettings.triage.my_triage.scroll_offset;
        // var offset = $(".ta-message-panel").position().top;
        // $('html, body').animate({scrollTop: (offset)}, 'fast');
        var offset = $('.ta-message-panel').offset().top - $('.ta-message-panel').height();
        $('html, body').animate({scrollTop: offset});
      }

      function triage_grpmove(item) {
        $('div.triagetip').remove();
        var stop_scroll = new Array();
        stop_scroll[0] = false;
        var proc_path = window.location.protocol + "//" + window.location.host + drupalSettings.path.baseUrl;
        var post_url = proc_path + 'triage-post';
        var keepgoing = true;
        var data = {};
        data.rows = [];
        var prt = item.parent().parent();
        if (item.parent().hasClass('mobile-zip')) {
          prt = item.parent().parent().parent().parent();
        }
        prt.find('form').each(function (index) {
          var formname = $(this).attr('id');
          var classes = $(this).parent('.triage-action-form').attr('class');
          if (classes.indexOf('mandatory') > -1) {
            switch (formname) {
              case 'triage-pov-form':
                if (!triage_income_validate()) {
                  keepgoing = false;
                  return;
                }
                break;
              case 'triage-status-form' :
                if (!triage_status_validate()) {
                  keepgoing = false;
                  return;
                }
                break;
              case 'triage-zip-form' :
                // alert('a');
                if (!triage_zip_validate()) {
                  keepgoing = false;
                  return;
                }
                break;
            }
          }
          var selector = "#" + formname;
          val = '';
          inputs = '';
          $(selector).find('input.triage-input, select.triage-input').each(function (index) {
            slct = $(this);
            inputs = inputs + slct.attr('id');
            if (inputs > '') {
              inputs = inputs + ","
            }
            if (slct.attr('type') == 'checkbox' || slct.attr('type') == 'radio') {
              if (slct.attr('checked')) {
                val = val + slct.val();
                if (val > '') {
                  val = val + ",";
                }
              }
            }
            else {
              val = val + slct.val();
              if (val > '') {
                val = val + ",";
              }
            }
          });
          var row = {
            form: formname, inputs: inputs, values: val
          };
          data.rows.push(row);
        });
        if (keepgoing) {
          triage_loading();
          $.post(post_url, data, function (response, status) {
            if (status == 'success') {
            }
          });
          $('.triage-loading').remove();
          triage_group_check(item);
        }
        if (stop_scroll[0]) {
          $('#edit-triage-last-info')[0].scrollIntoView(true);
          // var top = $('#div_' + element_id).position().top;
          // $(window).scrollTop(top);
        }
        else {
          // var offset = drupalSettings.triage.my_triage.scroll_offset;
          // $('html, body').animate({scrollTop: (offset)}, 'fast');
          var offset = $('.ta-message-panel').offset().top - $('.ta-message-panel').height();
          $('html, body').animate({scrollTop: offset});
        }
      }

      function triage_movebtns() {
        if ($('.path-triage .ta-navbar .triage-group-reset:visible').length === 0) {
          $('.triage-master').prepend($('.triage-group-reset:visible').detach());
        }
        if ($('.path-triage .ta-navbar .triage-group-submit:visible').length === 0) {
          $('.triage-master').append($('.triage-group-submit:visible').detach());
        }
      }

      function triage_group_check(item) {
        var triage_path = window.location.protocol + "//" + window.location.host;
        var cookiepath = triage_path + drupalSettings.path.baseUrl;
        var lang = drupalSettings.triage.my_triage.lang;
        var defa_lang = drupalSettings.triage.my_triage.default_lang;
        if (lang == defa_lang) {
          lang = '';
        }
        else {
          lang = lang + "/";
        }
        triage_path = triage_path + drupalSettings.path.baseUrl + lang + "triage/" + drupalSettings.triage.my_triage.vocab + "/";
        var thisclass = item.attr('class');
        var grpid = thisclass.replace('triage-group-submit next-', '');
        var msg = item.parent().children('.triage-group-reset').attr('class');

        if (typeof msg === "undefined") {
          $.cookie('triage_hist', '', {path: '/'});
          $.cookie('triage_last', '', {path: '/'});
          $.cookie('triage_current_step', 1, {path: '/'});
        }
        var pre_cook = $.cookie('triage_hist', {path: '/'});
        if (pre_cook === null) {
        }
        else {
          if (grpid != 0) {
            var ref = drupalSettings.triage.my_group[parseInt(grpid)];
            $.cookie('triage_hist', pre_cook + ">>" + ref, {path: '/'});
            $.cookie('triage_last', ref, {path: '/'});
            $.cookie('triage_current_step', 1, {path: '/'});
          }
        }
        //triage_progress(grpid);
        var url = triage_path + "0/1/" + grpid;
        var parent = null;
        parent = item.parent('.triage-group');
        $('.triage-group').hide();
        $('.grp-' + grpid).removeClass('hidden');
        $('.grp-' + grpid).show();

        if (grpid == 0) {
          $('.grp-' + grpid).hide();
          var dtid = drupalSettings.triage.direct_nid;
          if (typeof dtid == "undefined") {
            url = triage_path + "0/1/-1";
            triage_getdata(url);
          }
          else {
            url = triage_path + drupalSettings.triage.direct_nid;
            window.location.href = url;
          }
        }
        else {
          $('.triage-loading').remove();
          //triage_movebtns();
        }
      }

      function triage_grpreset(item) {
        triage_loading();
        var triage_path = window.location.protocol + "//" + window.location.host;
        var lang = drupalSettings.triage.my_triage.lang;
        var defa_lang = drupalSettings.triage.my_triage.default_lang;
        if (lang == defa_lang) {
          lang = '';
        }
        else {
          lang = lang + "/";
        }
        triage_path = triage_path + drupalSettings.path.baseUrl + lang + "triage/" + drupalSettings.triage.my_triage.vocab + "/";
        var thisclass = item.attr('class');
        var grpid = thisclass.replace('triage-group-reset prev-', '');
        var url = triage_path + "0/1/" + grpid;
        $('.triage-group').hide();
        $('.grp-' + grpid).show();
        $('.triage-content').html("");
        //triage_progress(grpid);
        $('.triage-loading').remove();
        // var offset = drupalSettings.triage.my_triage.scroll_offset;
        // $('html, body').animate({scrollTop: (offset)}, 'fast');
        var offset = $('.ta-message-panel').offset().top - $('.ta-message-panel').height();
        $('html, body').animate({scrollTop: offset});
      }

      function triage_reset(item) {
        triage_loading();
        var triage_path = window.location.protocol + "//" + window.location.host;
        var lang = drupalSettings.triage.my_triage.lang;
        var defa_lang = drupalSettings.triage.my_triage.default_lang;
        if (lang == defa_lang) {
          lang = '';
        }
        else {
          lang = lang + "/";
        }
        triage_path = triage_path + drupalSettings.path.baseUrl + lang + "triage/" + drupalSettings.triage.my_triage.vocab + "/";
        var thisclass = item.attr('class');
        var thisid = thisclass.replace('triage-reset prev-', '');
        var grp = ""
        if (thisid === "0") {
          grp = "/-1";
        }
        var url = triage_path + thisid + "/1" + grp;
        if (!$('.triage-master').length) {
          $('.triage-main-body').wrap("<div class='triage-master topcat' >");
          window.location = triage_path;

        }
        else {
          triage_getdata(url);
        }
      }


      function triage_loading() {
        $('div.triage-loading').remove();
        $('body').append("<div class='triage-loading'><i class='fa fa-spinner fa-spin'></i>Loading...</div>");
        $('div.triage-loading').show();
      }

      function triage_getdata(url) {
        var out = '';
        triage_loading();
        $.getJSON(url, function (data) {
          if (data) {
            out = out + data;
          }
          if (!$('.triage-master').hasClass('topcat')) {
            $(".triage-master").addClass('topcat');
          }
          $(".triage-master").html(out);
          out = $(".triage-master .triage-master").html();
          $(".triage-master").html(out);
          //$(".triage-content").html(out);
          $(".triage-loading").remove();
          // adjust_nav_css();
          triage_clicks();
          $(".triage-loading").remove();
          // var offset = drupalSettings.triage.my_triage.scroll_offset;
          // $('html, body').animate({scrollTop: (offset)}, 'fast');
          var offset = $('.ta-message-panel').offset().top - $('.ta-message-panel').height();
          $('html, body').animate({scrollTop: offset});
        });
      }

      function triage_clicks() {
        setsvg();
        if (!$('.triage-top').length) {
          $('.triage-intro').hide();
        }
        $('.bdspop').once('triage').click(function () {
          bdslegalloadPopup('bdslegalpopup');
        });
        $('.del-row').once('triage').click(function (event) {
          var orig_url = window.location.href;
          event.preventDefault();
          var proc_path = window.location.protocol + "//" + window.location.host + drupalSettings.path.baseUrl;
          var post_url = proc_path + 'triage_del';
          var id = $(this).attr('id');
          id = id.replace('del', "");
          var data = id;
          $.post(post_url, data, function (data, status) {
            if (status == 'success') {
              if (data > "") {
                $('#bdslegalpopup').html(data);
                bdslegalloadPopup('bdslegalpopup');
                $('.delbutton').once('triage').click(function (event) {
                  event.preventDefault();
                  var proc_path = window.location.protocol + "//" + window.location.host + drupalSettings.path.baseUrl;
                  var id = $(this).attr('id');
                  id = id.replace('del-', "");
                  var post_url = proc_path + 'triage_delete';
                  var data = id;
                  $.post(post_url, data);
                  bdslegaldisablePopup('bdslegalpopup');
                  popupStatus = 0;
                  window.location.href = orig_url;

                });
                $('.cancelbutton').once('triage').click(function (event) {
                  bdslegaldisablePopup('bdslegalpopup');
                  popupStatus = 0;
                });
              }
            }
          });
        });
        $('#edit-triage-income').change(function () {
          if ($(this).val() == 0) {
            $('#edit-triage-income-period-annual').attr('checked', 'checked');
            $('.triage-income_period').hide();
          }
          else {
            $('.triage-income_period').show();
          }
        });
        alreadyclicked = false;
        //$('input[type=radio][name=triage_area_info]').unbind();
        if ($('.path-triage .triage-submit').length) {
          //$('.path-triage .triage-cat-text').remove();
        }
        $('input[type=radio][name=triage_area_info]').change(function () {
          if (this.value == '0') {
            $('#triage-page-wrapper').css('opacity', '0.2');
            triage_loading();
            var url = Drupal.settings.my_triage.oos_url;
            if (url.indexOf("http") === -1) {
              url = window.location.protocol + "//" + window.location.host;
              url += Drupal.settings.basePath + Drupal.settings.my_triage.oos_url;
            }
            window.location.href = url;
          }
        });
        // unsets click and  functions so that we don't get doubles
        $('.triage-row').once('triage').click(function () {
          var el = $(this);
          triage_moveon($(el));
          return false;
        });
        $(".triage-reset").once('triage').click(function () {
          var el = $(this);
          triage_reset($(el));
        });
        $(".triage-submit").once('triage').click(function () {
          var el = $(this);
          $.cookie('triage_finished', 1, {path: '/'});
          triage_submit(el);
          return false;
        });
        $(".triage-group-submit").once('triage').click(function () {
          var el = $(this);
          triage_grpmove($(el));
        });
        $(".triage-group-reset").once('triage').click(function () {
          $('div.triagetip').remove();
          triage_loading();
          var el = $(this);
          if (el.hasClass('bypass')) {
            el.removeClass('bypass');
            var triage_path = window.location.protocol + "//" + window.location.host;
            triage_path += drupalSettings.path.baseUrl + "triage/" + drupalSettings.triage.my_triage.vocab + "/";
            window.location.href = triage_path;
            triage_loading();
          }
          triage_grpreset($(el));
        });
        $('.triage-restart').once('triage').click(function () {
          var el = $(this);
          triage_restart();
        });

        $('.tr-help').each(function () {
          $(this).after("<button aria-label='Additional Information for " + $(this).attr('data-topic') + "' class='tr1-help'> </button>");
        });
        $('.tr1-help').click(function () {
          $('div.triagetip').remove();
          var item = null;
          varthisclass = "";
          item = $(this).prev('.tr-help');
          if (!$(this).hasClass('expanded')) {
            $('.tr1-help').removeClass('expanded');
            // $('.tr1-help').html('&#xf05a;');
            $('.tr1-help').html(' ');
            // $(this).html('&#xf057;');
            $(this).html(' ');
            $(this).addClass('expanded');
            //$(this).attr('title', "Close Help");
            $(this).attr('aria-label', "Close Help");
            if (item) {
              thisclass = item.prop('class');
            }
            else {
              thisclass = "";
            }
            var tid = thisclass.replace('nokids', '');
            tid = tid.replace('tr-help', '');
            tid = tid.replace('triage-row trow-', '');
            tid = tid.trim();
            var pick = '.thelp-' + tid;
            var text = $(pick).html() + '<div class="arrow"></div>';
            if ($('div.triagetip').length == 0) {
              $('<div class="triagetip" tabindex="-1" ></div>').appendTo('body');

            }
            $('.triagetip').html(text);
            var thisheight = $('.triagetip').height();
            if ($(this).offset().left - 400 > 0) {
              var tPosX = $(this).offset().left - 400;
            }
            else {
              $(this).width = $(window).width();
              var tPosX = 0
            }
            var tPosY = item.offset().top - thisheight - 60;
            $('div.triagetip').css({
              'position': 'absolute', 'top': tPosY, 'left': tPosX
            });
            $('div.triagetip').show().focus();
            // $('<button
            // class="x-it">&#xf057;</button>').appendTo('.triagetip');
            $('<button aria-label="Close Tooltip" class="x-it"> </button>').appendTo('.triagetip');
            $('.x-it').click(function () {
              $(this).parent().remove();
              $('.tr1-help.expanded').each(function () {
                $(this).attr('aria-label', "Additional Info for " + $(this).prev('.tr-help').attr('data-topic'));
              });
              $('.tr1-help').removeClass('expanded');
              // $('.tr1-help').attr('aria-label','Close Tooltip');
              // $('.tr1-help').html('&#xf05a;');
              $('.tr1-help').html(' ');
            })
          }
          else {
            // $(this).html('&#xf05a;');
            $(this).html(' ');
            $(this).removeClass('expanded');
            $(this).attr('title', "Get Info");
            $(this).attr('aria-label', "Additional Info for " + $(this).prev('.tr-help').attr('data-topic'));
            $('div.triagetip').remove();
          }
        });
        if (!$('.triage-master').length) {
          $('.triage-main-body').wrap("<div class='triage-master'>");
        }
        $(".triage-master").find("button").first().focus();
      }

      function triage_is_changed(item) {
        //Sorts through inputs to see if any haven't been updated yet and
        // returns the id so that we call change() on submit
        item = item || ".triage-forms";
        var ret = "";
        var selector = item + ' input.triage-input';
        $(selector).each(function (index) {
          slct = $(this);
          var last = slct.data("last");
          if (slct.attr('type') == 'checkbox' || slct.attr('type') == 'radios') {
            if (last !== slct.attr('checked')) {
              var newval = slct.attr('checked');
              slct.data('last', newval);
              ret = slct.attr('id');
              return ret;
            }
          }
          else {
            if (slct.val() !== last) {
              var newval = slct.val();
              slct.data('last', newval);
              ret = slct.attr('id');
              return ret;
            }
          }
        });
        return ret;
      }

      function triage_income_validate() {
        triage_clicks();
        var msg = '';
        $('#edit-triage-household').removeClass('triage-alert');
        $('#edit-triage-income').removeClass('triage-alert');
        $('div.triage-notice').remove();
        var ret = true;
        var size = $('#edit-triage-household').val();
        msg = isTriageInteger(size, "Household Size");
        if (msg > "") {
          $('#edit-triage-household').val('');
          $('#triage-pov-form').prepend('<div class="triage-notice">' + msg + '</div>');
          $('#edit-triage-household').addClass('triage-alert');
          return false;
        }
        if (size === '0' || size.trim() === '') {
          if (size === '0') {
            msg = "Household size must be at least 1";
            $('#edit-triage-household').addClass('triage-alert');
            $('#triage-pov-form').prepend('<div class="triage-notice">' + msg + '</div>');
            return false;
          }
          else {
            msg = "Household size must be entered";
            $('#edit-triage-household').addClass('triage-alert');
            $('#triage-pov-form').prepend('<div class="triage-notice">' + msg + '</div>');
            return false;
          }
        }
        else {
          $('#edit-triage-household').removeClass('triage-alert');
        }
        var income = $('#edit-triage-income').val();
        msg = isTriageInteger(income, "Income");
        if (msg > "") {
          $('#edit-triage-income').val('');
          $('#triage-pov-form').prepend('<div class="triage-notice">' + msg + '</div>');
          return false;
        }
        if (income === null || income.trim() === '') {
          msg = "Household income must be entered";
          $('#edit-triage-income').addClass('triage-alert');
          $('#triage-pov-form').prepend('<div class="triage-notice">' + msg + '</div>');
          return false;
        }
        else {
          $('#edit-triage-income').removeClass('triage-alert');
        }
        var pc = triageComputePOV(size, income);
        if (pc > 400) {
          if (!confirm("Are you sure $" + income + " is your MONTHLY income?")) {
            return false
          }
        }
        return true;
      }

      function triage_zip_validate() {
        triage_clicks();
        // alert('b');
        ret = false;
        $('#edit-triage-zip').parent('.input-group').removeClass('alert');
        $('div.triage-notice').remove();
        if ($('.city-fail').length) {
          ret = false;
        }
        else {
          if ($('#triage_city .found-zip').length) {
            ret = true;
          }
        }
        if (!ret) {
          $('div.triage-notice').remove();
          $('#edit-triage-zip').parent('.input-group').addClass('triage-alert');
          var msg = 'Values outlined in red are required';
          $('#triage-zip-form').prepend('<div class="triage-notice">' + msg + '</div>');
        }
        // alert('c:' + ret);
        return ret;
      }

      function triage_status_validate() {
        triage_clicks();
        $('#edit-triage-status').removeClass('alert');
        $('div.notice').remove();
        var ret = true;
        var num = $('#edit-triage-status input:checked').length;
        if (num === 0) {
          ret = false;
        }
        if (!ret) {
          $('div.notice').remove();
          $('#edit-triage-status').addClass('alert');
          $('#triage-status-form').prepend('<div class="notice">Please Select one or more options</div>');
        }
        return ret;
      }

      function triage_submit(item) {
        var thisclass = item.attr('class');
        var tid = thisclass.replace('triage-submit tid-', '');
        var changed = triage_is_changed();
        var proc_path = window.location.protocol + "//" + window.location.host + drupalSettings.path.baseUrl;
        var write_url = proc_path + 'triage_write_history';
        var lang = drupalSettings.triage.my_triage.lang;
        var defa_lang = drupalSettings.triage.my_triage.default_lang;
        if (lang == defa_lang) {
          lang = '';
        }
        else {
          lang = lang + "/";
        }
        // var len = drupalSettings.triage.my_triage.tid.length;
        // var tid = drupalSettings.triage.my_triage.tid[len - 1];
        var url = proc_path + lang + "triage_actions_process/" + tid;
        $(".triage-loading").remove();
        triage_loading();
        $.cookie('triage_last_tid', tid, {path: '/'});
        if (changed == "") {
          $.cookie('triage_completed', 1, {path: '/'});
          window.location.href = url;
        }
        else {
          is_changed = true;
          $('#' + changed).change();
          $(document).ajaxComplete(function () {
            if (is_changed == true) {
              is_changed = false;
              $.cookie('triage_completed', 1, {path: '/'});
              window.location.href = url;
              $(".triage-loading").remove();
            }
          });
          $(".triage-loading").remove();
        }
      }

      function isTriageInteger(s, fn) {
        if (typeof fn === 'undefined') {
          fn = 'This Field';
        }
        var error = "";
        var i;
        s = s.replace(",", '');
        s = s.replace("$", '');
        var find = s.indexOf(".");
        if (find > -1) {
          s = s.substring(0, find);
        }
        for (i = 0; i < s.length; i++) {
          // Check that current character is number.
          var c = s.charAt(i);
          if (((c < "0") || (c > "9"))) {
            error = fn + " must be a number only, with no commas or dollar sign\n";
          }
        }
        if (fn == "Income") {
          $("#edit-triage-income").val(s);
        }
        // All characters are numbers.
        return error;
      }

      function triageComputePOV(size, inc, period) {
        if (typeof period === "undefined") {
          period = "Monthly";
        }
        var div_by = 12;
        switch (period) {
          case 'Weekly':
            div_by = 52;
            break;
          case 'Bi-Weekly':
            div_by = 26;
            break;
          case 'Monthly':
            div_by = 12;
            break;
          case 'Annual':
            div_by = 1;
            break;
        }
        vals = drupalSettings.triage.povguides;
        var base = vals[size] / div_by;
        return Math.round(inc * 100 / base);
      }

      setsvg();

      function setsvg() {
        $('img.svg').each(function () {
          var $img = $(this);
          var imgID = $img.attr('id');
          var imgClass = $img.attr('class');
          var imgURL = $img.attr('src');

          $.get(imgURL, function (data) {
            // Get the SVG tag, ignore the rest
            var $svg = $(data).find('svg');

            // Add replaced image's ID to the new SVG
            if (typeof imgID !== 'undefined') {
              $svg = $svg.attr('id', imgID);
            }
            // Add replaced image's classes to the new SVG
            if (typeof imgClass !== 'undefined') {
              $svg = $svg.attr('class', imgClass + ' replaced-svg');
            }

            // Remove any invalid XML tags as per http://validator.w3.org
            $svg = $svg.removeAttr('xmlns:a');

            // Replace image with new SVG
            $img.replaceWith($svg);

          }, 'xml');
        });
      }

      function bdslegalloadPopup(popid) {
        //loads popup only if it is disabled
        var speed = 500;
        if (popupStatus === 0) {
          popupStatus = 1;
          $("#triage-popup").css({"opacity": "0.7"});
          $("#triage-popup").delay(speed).fadeIn("slow");
          $("#" + popid).delay(speed).show('slow', function () {
            $("#" + popid + " a:first").focus();
          });
          bdslegalcenterPopup('bdslegalpopup');
        }
      }

      //alert('hw');
      //disabling popup with jQuery magic!
      function bdslegaldisablePopup(popid) {
        //disables popup only if it is enabled
        if (popupStatus === 1) {
          $("#triage-popup").fadeOut("slow");
          $("#" + popid).fadeOut("slow");
        }
      }

      //centering popup
      function triage_getpopup(url) {
        var out = '';
        $.getJSON(url, function (data) {
          if (data) {
            out = out + data;
          }
        });
      }

      function bdslegalcenterPopup(popid) {
        //request data for centering
        // var windowWidth = document.documentElement.clientWidth;
        // var windowHeight = document.documentElement.clientHeight;
        var windowWidth = $(window).width();
        var windowHeight = screen.height;
        var popupHeight = $("#" + popid).height();
        var popupWidth = $("#" + popid).width();
        //centering
        $("#" + popid).css({
          "position": "fixed",
          "top": windowHeight / 2 - popupHeight / 2 - 100,
          "left": windowWidth / 2 - popupWidth / 2
        });
        //only need force for IE6
        $("#triage-popup").css({
          "height": windowHeight
        });
        //$('.triage-pop-wrap a.yes-popbutton').focus();
      }

      //LOADING POPUP
      //Click the button event!
      //CLOSING POPUP
      $("#bdslegalpopupClose").click(function () {
        bdslegaldisablePopup('bdslegalpopup');
        popupStatus = 0;
      });
      $(".no-popbutton").click(function () {
        var url = window.location.protocol + "//" + window.location.host + drupalSettings.path.baseUrl + "triage_nothanks";
        //alert(url);
        drupalSettings.triage.popup.use_pop = false;
        var cookiepath = triage_path + drupalSettings.path.baseUrl;
        $.cookie('triage_use_pop', false, {path: '/'});
        triage_getpopup(url);
        bdslegaldisablePopup('bdslegalpopup');
        popupStatus = 0;
      });
      $("#triage-popup").click(function () {
        bdslegaldisablePopup('bdslegalpopup');
        popupStatus = 0;
      });
      $(document).keypress(function (e) {
        if (e.keyCode === 27) {
          bdslegaldisablePopup('bdslegalpopup');
          popupStatus = 0;
        }
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
