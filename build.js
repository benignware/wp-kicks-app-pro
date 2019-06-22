#!/usr/bin/env node

const path = require('path');
const fs = require('fs');
const { promisify } = require('util');
const sass = require('node-sass');
const renderAsync = promisify(sass.render);
const readFileAsync = promisify(fs.readFile);
const writeFileAsync = promisify(fs.writeFile);
const sassExtract = require('sass-extract');
const { sync: mkdirp } = require('mkdirp');
let { parse, stringify } = require('scss-parser');
let createQueryWrapper = require('query-ast');

function rgbaToHex(orig){
 var rgb = orig.replace(/\s/g,'').match(/^rgba?\((\d+),(\d+),(\d+)/i);
 return (rgb && rgb.length === 4) ? "#" +
  ("0" + parseInt(rgb[1],10).toString(16)).slice(-2) +
  ("0" + parseInt(rgb[2],10).toString(16)).slice(-2) +
  ("0" + parseInt(rgb[3],10).toString(16)).slice(-2) : orig;
}

function hexToRgba(hex) {
    var c;
    if(/^#([A-Fa-f0-9]{3}){1,2}$/.test(hex)){
        c= hex.substring(1).split('');
        if(c.length== 3){
            c= [c[0], c[0], c[1], c[1], c[2], c[2]];
        }
        c= '0x'+c.join('');
        return 'rgba('+[(c>>16)&255, (c>>8)&255, c&255].join(',')+',1)';
    }
    throw new Error('Bad Hex');
}

const parseRgba = (string, alpha = 1) => {
  let r, g, b, a;

  if (string.trim().startsWith('#')) {
    [ r = 0, g = 0, b = 0, a = 1 ] = (string.match(/\w\w/g) || []).map(x => x ? parseInt(x, 16) : 0);
  } else {
    [, r, g, b, a ] = string.match(/^rgba?\((\d+),(\d+),(\d+)/i);
  }

  a = a || alpha;

  return {
    r, g, b, a
  }
}

const hex2rgba = (hex, alpha = 1) => {
  const [r, g, b] = hex.match(/\w\w/g).map(x => parseInt(x, 16));
  return `rgba(${r},${g},${b},${alpha})`;
};

function adjustColorBrightness(col, amt, usePound = false) {
  if (col[0] == "#") {
    col = col.slice(1);
    usePound = true;
  }

  var num = parseInt(col, 16);

  var r = (num >> 16) + amt;

  if (r > 255) r = 255;
  else if  (r < 0) r = 0;

  var b = ((num >> 8) & 0x00FF) + amt;

  if (b > 255) b = 255;
  else if  (b < 0) b = 0;

  var g = (num & 0x0000FF) + amt;

  if (g > 255) g = 255;
  else if (g < 0) g = 0;

  let result = (g | (b << 8) | (r << 16)).toString(16);

  result = r === 0 ? '00' + result : result;

  return (usePound ? '#' : '') + result;
}

const parseVariable = (string) => (string.match(/var\s*\(\s*--([a-z_-]*)/) || []).pop();

const build = async(entry, dest = 'dist') => {

  /*
  const theme = {
    primary: '#ff0000',
    secondary: 'gray',
    'link-color': 'var(--primary)',
    spacer: '1rem'
  };
  */

  const include = [
    'font-family-base'
  ];

  const exclude = [
    'emphasized-link-hover-darken-percentage',
    'theme-color-interval',
    'modal-dialog-margin',
    'modal-dialog-margin-y-sm-up',
    'alert-padding-x',
    'jumbotron-padding',
    'table-border-width',
    'border-width',
    'btn-padding-x',
    'input-btn-padding-x',
    'btn-padding-x-sm',
    'input-btn-padding-x-sm',
    'btn-padding-x-lg',
    'input-btn-padding-x-lg',
    'font-size-base',
    'custom-control-indicator-border-width',
    'input-border-width',
    'input-btn-border-width',
    'custom-range-thumb-focus-box-shadow-width',
    'input-focus-width',
    'input-btn-focus-width',
    'rfs-font-size-unit',
    'rfs-breakpoint-unit',
    // 'grid-gutter-width'
  ];

  // const file = path.resolve(__dirname, './scss/bootstrap.scss');

  const file = entry;

  const implicit = {};
  const manifest = {};

  const options = {
    file,
    includePaths: [
      path.resolve(process.cwd(), 'node_modules')
    ],
    functions: {
      /*
      'var($name)': function(name) {
        const { Color, String } = sass.types;
        const key = name.getValue().replace(/^--/, '');

        if (theme[key]) {
          return new String(`${theme[key]}`);
        }

        return new String(`var(${name.getValue()})`);
      },
      */
      'color-yiq($color)': function(color) {
        // console.log('COLOR-YIQ', color);
        const { Color, String } = sass.types;

        return new Color(0, 0, 0, 1);
      },
      'red($color)': function(color) {
        const { Color, Number } = sass.types;

        return new Number(0);
      },
      'green($color)': function(color) {
        const { Color, Number } = sass.types;

        return new Number(0);
      },
      'blue($color)': function(color) {
        const { Color, Number } = sass.types;

        return new Number(0);
      },
      'mix($color1, $color2, $weight)': function(color1, color2, weight = '50%') {
        const { Color, String } = sass.types;

        return new Color(0, 0, 0, 1);
      },
      'rgba($red, $green: null, $blue: null, $alpha: 1)': function(red, green, blue, alpha = 1) {
        const { Color, String } = sass.types;

        // console.log('ARGS: ', red, green, blue, alpha);

        if (red instanceof String) {
          // console.log('STRING:', red.getValue());

          const variable = parseVariable(red.getValue());
          // console.log('variable', variable);

          if (variable) {

            // console.log('variable', variable);
            return new String(`var(--${variable}-dark)`);
          }

          const { r, g, b, a } = parseRgba(red.getValue(), green.getValue());

          return new Color(r, g, b, a);

        } else if (red instanceof Color) {
          return new Color(
            red.getR(),
            red.getG(),
            red.getB(),
            green.getValue()
          );
        } else {
          return new Color(
            red.getValue(),
            green.getValue(),
            blue.getValue(),
            alpha.getValue()
          );
        }

        // console.log('color ...', r, g, b, a);

        return new Color(r, g, b, a);
      },
      'darken($color, $amount)': function(color, amount) {
        const { Color, String } = sass.types;

        // console.log('DARKEN: ', color.getR, amount.getValue());

        if (color instanceof Color) {

          const hex = rgbaToHex(`rgba(${color.getR()},${color.getG()},${color.getB()},${color.getA()})`);

          const transformed = adjustColorBrightness(hex, amount.getValue() * -1);
          const { r, g, b, a } = parseRgba(transformed, color.getA());

          // console.log('darken...', hex, transformed, r, g, b, a);

          return new Color(r, g, b, a);
        }

        if (color instanceof String) {
          const variable = parseVariable(color.getValue());

          // console.log('DARKEN ', color.getValue(), variable);

          // Implicit variable

          const varName = `${variable}-darken-${Math.round(amount.getValue())}`;
          implicit[varName] = {
            type: 'color',
            name: varName,
            source: variable,
            implicit: true,
            filter: {
              name: 'darken',
              amount: amount.getValue()
            }
          }

          // console.log('implicit', implicit);

          if (variable) {
            // console.log('daRKEN REPLACE VAR..', variable);
            return new String(`var(--${varName})`);
          }
        }

        return color;
      },
      'lighten($color, $amount)': function(color, amount) {
        const { Color, String } = sass.types;

        //' console.log('LIGHTEN: ', color.getR, amount.getValue());

        if (color instanceof Color) {

          const hex = rgbaToHex(`rgba(${color.getR()},${color.getG()},${color.getB()},${color.getA()})`);
          const { r, g, b, a } = parseRgba(adjustColorBrightness(hex, amount.getValue()), color.getA());

          return new Color(r, g, b, a);
        }

        if (color instanceof String) {
          const variable = parseVariable(color.getValue());
          // console.log('variable', variable);

          // Implicit variable

          const varName = `${variable}-lighten-${Math.round(amount.getValue())}`;
          implicit[varName] = {
            type: 'color',
            name: varName,
            source: variable,
            implicit: true,
            filter: {
              name: 'lighten',
              amount: amount.getValue()
            }
          }

          // console.log('implicit', implicit);

          if (variable) {
            return new String(`var(--${varName})`);
          }
        }

        return color;
      }
    }
  };

  const variablesFile = path.resolve(__dirname, 'node_modules/bootstrap/scss/_variables.scss');

  try {
    const { vars: { global: vars = {} } = {} } = await sassExtract.render(options);

    const variables = Object.assign({}, ...Object.entries(vars).map(([ key, result ]) => {
      let { type, value, declarations: [{ expression, position }] } = result;

      const name = key.replace(/^\$/, '');
      const isExcluded = exclude.includes(name);

      if (isExcluded) {
        return null;
      }

      const isUnit = (expression.match(/unit/) || []).length > 0;

      if (isUnit) {
        return null;
      }

      let raw = value;

      if (type === 'SassList') {
        if (!expression.match(/^\s*\(/) && value.every((item) => item.type === 'SassString') && value.some((item) => [ 'sans-serif', 'serif', 'monospace' ].includes(item.value))) {
          raw = value.map((item) => item.value).join(`${result.separator} `);
        }
      }

      if (type === 'SassColor') {
        raw = `rgba(${value.r},${value.g},${value.b},${value.a})`;
      }

      if (type === 'SassNumber' && !result.unit) {
        // raw = `#{${value}}`;
        return null;
      }

      if (type === 'SassNumber') {
        // raw = `#{${value}}`;
        raw = `${value}${result.unit}`;
      }

      if (type === 'SassString') {
        raw = `${value}`;
        // return null;
      }

      if (key.indexOf('heading') >= 0) {
        console.log('VAR: ', key, type, raw, result);
      }

      if (typeof raw === 'string') {



        // console.log('VAR: ', key, type, raw);

        return {
          [name]: {
            type: {
              'SassNumber': 'number',
              'SassColor': 'color'
            }[type] || 'string',
            value: raw,
            expression: result.declarations[0].expression
          }
        };
      }


      return null;
    }).filter((item) => {
      // console.log('item', item);
      return item;
    }));

    // console.log('manifest: ', manifest);

    // const variables = { ...theme };

    // console.log('variables', theme, variables);

    let data = await readFileAsync(file, 'utf-8');

    // data  = '';



    const varis = Object.assign({}, ...Object.entries(variables).map(([ key, { value: source, type, expression } ]) => {
      // console.log('DEFINE VAR', key, value, expression);
      let value = source;
      let ast = parse(expression);

      // Create a function to traverse/modify the AST
      let $ = createQueryWrapper(ast);

      const res = $('variable').filter(({ node }) => {
        return Object.keys(variables).includes(node.value);
      });

      let expr = expression.trim();

      if (res.nodes.length > 0) {
        for (let { node } of res.nodes) {
          expr = expr.replace(new RegExp(`\\$(${node.value})\\s*(?:\!default)?`), `var(--$1)`);
        }
      }

      if (expr.match(/^var\s*\(--[a-z_-]*\)$/)) {
        value = expr;
      } else {
        const [match, name, variable, amount ] = expr.match(/^(darken|lighten)\s*\(\s*var\s*\(--([a-zA-Z_-]*)\)\s*,\s*(\d+)/) || [];


        if (match) {
          const implicitName = `${variable}-${name}-${amount}`;
          value = `var(--${implicitName})`;

          implicit[implicitName] = {
            implicit: true,
            type: 'color',
            source: variable,
            filter: {
              name, amount
            }
          };
        }
      }

      return {
        [key]: {
          default: source,
          value,
          type
        }
      };
    }));

    data = `${Object.entries(varis).map(([ key, obj ]) => {
      let { value: sourceValue } = obj;
      let value = `var(--${key})`;

      if (sourceValue.match(/^var\s*\(--[a-z_-]*\)$/)) {
        // value = sourceValue;
      }

      return `$${key}: ${value};`;
    }).join('\n')}` + data;

    data = data + `:root {\n${Object.entries(varis).map(([ key, { value } ]) => `--${key}: ${value};`).join('\n')}\n}\n`;

    const result = await renderAsync({
      ...options,
      includePaths: options.includePaths.concat(path.dirname(file)),
      data
    });

    if (!fs.existsSync(path.dirname(dest))) {
      mkdirp(path.dirname(dest));
    }

    const ok = await writeFileAsync(dest, result.css);

    // console.log('implicit', implicit);
    const manifestOk = await writeFileAsync(`${dest}.json`, JSON.stringify({
      ...varis,
      ...implicit
    }, null, 2));

  } catch (error) {
    console.log('ERROR', error);
  }


};

const main = async() => {
  await build(
    path.resolve(__dirname, './scss/bootstrap/bootstrap.scss'),
    path.join(__dirname, 'dist/bootstrap.css')
  );

  await build(
    path.resolve(__dirname, './scss/bootstrap/bootstrap-editor.scss'),
    path.join(__dirname, 'dist/bootstrap-editor.css')
  );

};

main();
