{% autoescape "js" %}
/**
 * Based on directory.js by Hay Kranen
 * <https://github.com/hay/wiki-tools/blob/68cb143/public_html/common/directory.js>
 */
$(document).ready(function() {
  "use strict";
  var $tools = $(".toolinfo")
    , $w = $(window)
    , $q = $("#q")
    , $s = $("#status")
    , $all = $(".all-toolinfo")
    , $msg = $("#status p")
    , db = {
{%- for tool in tools -%}
{%- for info in tool.toolinfo -%}
      "{{ info.name }}": { au: {{ info.author|json_encode()|raw }}, ky: {{ info.keywords|json_encode()|raw }}, mt: {{ info.maintainers|json_encode()|raw }}, ft: {{ info.fulltext|json_encode()|raw }} },
{%- endfor -%}
{%- endfor %}
      "__": {}
    };

  function debounce(fn, wait, now) {
    var t;
    return function() {
      var ctx = this
        , args = arguments
        , later = function() {
          t = null;
          if (!now) {
            fn.apply(ctx, args);
          }
        }
        , callNow = now && !t;
      clearTimeout(t);
      t = setTimeout(later, wait);
      if (callNow) {
        fn.apply(ctx, args);
      }
    }
  }
  function route() {
    var h = window.location.hash.replace('#!/', '');
    if (!h) {
      return { action: 'showall', val: true };
    }
    var p = h.split("/");
    return { action: p[0], val: decodeURIComponent(p[1]) };
  }
  function apply(fn) {
    var cnt = 0;
    $tools.each(function() {
      var $this = $(this)
        , tool = $this.attr("data-tool")
        , info = db[tool];
      if (fn(info)) {
        cnt++;
        $this.show();
      } else {
        $this.hide();
      }
    });
    return cnt;
  }
  function flash(msg) {
    if (msg !== false) {
      $msg.html(msg);
      $s.show();
    } else {
      $s.hide();
    }
    window.scrollTo(0, 0);
  }
  function filter(r) {
    if (r.action === 'search' && r.val !== '') {
      var cnt = apply(function(t) {
        return t.ft.indexOf(r.val) !== -1;
      });
      $q.val(r.val);
      flash("Found " + cnt + " tool(s) for <strong>'" + r.val + "'</strong>.");
    } else if (r.action === 'keyword') {
      var cnt = apply(function(t) {
        return t.ky.map(function(k) {
          return k.toLowerCase();
        }).indexOf(r.val.toLowerCase()) !== -1;
      });
      $q.val('');
      flash(
        "Found " + cnt + " tools(s) for " + r.action +
        " <strong>'" + r.val + "'</strong>.");
    } else if (r.action === 'author') {
      var cnt = apply(function(t) {
        return t.au.indexOf(r.val) !== -1;
      });
      $q.val('');
      flash(
        "Found " + cnt + " tools(s) for " + r.action +
        " <strong>'" + r.val + "'</strong>.");
    } else if (r.action === 'maintainer') {
      var cnt = apply(function(t) {
        return t.mt.indexOf(r.val) !== -1;
      });
      $q.val('');
      flash(
        "Found " + cnt + " tools(s) for " + r.action +
        " <strong>'" + r.val + "'</strong>.");
    } else {
      $tools.show();
      $q.val('');
      flash(false);
    }
  }

  $("#search").submit(function(e) {
    e.preventDefault();
  }).show();
  $w.on('hashchange', function() {
    filter(route());
  });
  $all.on('click', function() {
    $s.hide();
    window.location.hash = '!/all';
  });
  $q.on('keyup', debounce(function() {
      window.location.hash = '!/search/' + $q.val();
    }, 250
  ));
  filter(route());
});
{% endautoescape %}
