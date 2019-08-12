(function($, window) {
  var THEME_VARS_URL = ThemalizeSettings.theme_vars_url;
  var THEME_DEFINITONS = JSON.parse(ThemalizeSettings.theme_vars);

  var updating = false;

  function trim(input) {
    return input && typeof input === 'string' ? input.replace(/^\s+|\s+$|\s+(?=\s)/g, '') : '';
  }

  function humanize(input) {
    var words = input.split(/(?:(?=[A-Z])|-|_)/);

    return words.map(function(string) {
      return string.charAt(0).toUpperCase() + string.slice(1);
    }).join(' ');
  }

  function getLabel(value, ref) {
    return trim(value) + ' (' + (ref ? humanize(ref) : 'Default') + ')';
  }

  function setVar(name, value) {
    document.documentElement.style.setProperty('--' + name, value);
  }

  function getVar(name) {
    return window.getComputedStyle(document.documentElement).getPropertyValue('--' + name);
  }

  function isVarRef(value) {
    return typeof value === 'string' && value.match(/^\s*var\s*\(/);
  }

  function getVarRef(value) {
    return value.replace(/^\s*var\s*\(--([a-zA-Z0-9-_]+)\)/, '$1');
  }

  function getVarType(name, value = '') {
    return THEME_DEFINITONS[name] && THEME_DEFINITONS[name].type;
  }

  function getFontValue(value) {
    return value.split(/,/).map(function(value) {
      return value.replace(/(^["']+)|(["']+$)/g, '');
    })[0];
  }

  function updateSetting(key, value) {
    // Immediate update style and controls
    setVar(key, value);
    updateControls([ key ], (function(key, value) {
      // TODO: Use babel ;-)
      var custom = {};
      custom[key] = value;
      return custom;
    })(key, value));

    wp.customize( key, function( obj ) {
      obj.set( value );

      var vars = wp.customize.get();

      $.ajax({
        url: THEME_VARS_URL,
        type: "POST",
        data: {
          data: vars
        },
        dataType: "json",
        success: function(result) {
          // Update theme variables including implicit vars
          for (var name in result) {
            setVar(name, result[name]);
          }
          updateControls([ key ]);
        }
      });
    });
  }

  function updateControls(exclude = [], custom = {}) {
    var vars = wp.customize.get();

    $('*[data-theme-control]').each(function() {
      var $this = $(this);
      var controlType = $(this).data('theme-control');
      var key = $(this).data('theme-setting');
      var type = getVarType(key);
      var value = custom[key] || vars[key];
      var ref = isVarRef(value) ? getVarRef(value) : null;

      if (ref && !exclude.includes(key)) {
        value = getVar(ref);

        if (type === 'font') {
          value = getFontValue(value);
        }

        value = trim(value);

        updating = true;

        switch (controlType) {
          case 'color-picker':
            $this.wpColorPicker('color', value);
            break;
          default:
            // $this.val(value);
            var defaultLabel = getLabel(value, ref);

            if (this.nodeName.toLowerCase() === 'select') {
              $this.find('option[value=\'\']').each(function() {
                this.innerHTML = defaultLabel;
              });
            }

            $this.attr('placeholder', defaultLabel);
            break;
        }

        updating = false;
      }
    });
  }

  $(function() {
    window.requestAnimationFrame(function() {
      $('*[data-theme-control]').each(function() {
        var $this = $(this);
        var controlType = $(this).data('theme-control');
        var key = $(this).data('theme-setting');
        var type = getVarType(key);
        var defaultValue = $(this).data('theme-default');
        var ref = isVarRef(defaultValue) ? getVarRef(defaultValue) : null;

        var orig = defaultValue;
        defaultValue = ref ? getVar(ref) : defaultValue;

        if (type === 'font') {
          defaultValue = getFontValue(defaultValue);
        }

        defaultValue = trim(defaultValue);

        console.log('SET VAR: ', key, defaultValue);

        var defaultLabel = getLabel(defaultValue, ref);

        switch (controlType) {
          case 'color-picker':
            $this.val( defaultValue );

            $this.wpColorPicker({
              defaultValue: defaultValue,
              change: function(event) {
                if (!updating) {
                  updateSetting(key, $this.wpColorPicker( 'color' ));
                }
              },
              clear: function(event) {
                var value = ref ? getVar(ref) : defaultValue;
                $this.wpColorPicker( 'color', value );
              }
            });
            break;
          default:
            $this.attr( 'placeholder', defaultLabel );
            if (this.nodeName.toLowerCase() === 'select') {
              $this.find('option[value=\'\']').each(function() {
                this.innerHTML = defaultLabel;
              });
            }
            $this.on('change', function(event) {
              if (!updating) {
                updateSetting(key, $this.val());
              }
            });
            break;
        }
      });
    });

  });
})(jQuery, window);
