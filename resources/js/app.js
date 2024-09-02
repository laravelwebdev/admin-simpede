import CodeMirror from 'codemirror'
import Nova from './nova.js'

import 'codemirror/mode/markdown/markdown'
import 'codemirror/mode/javascript/javascript'
import 'codemirror/mode/php/php'
import 'codemirror/mode/ruby/ruby'
import 'codemirror/mode/shell/shell'
import 'codemirror/mode/sass/sass'
import 'codemirror/mode/yaml/yaml'
import 'codemirror/mode/yaml-frontmatter/yaml-frontmatter'
import 'codemirror/mode/nginx/nginx'
import 'codemirror/mode/xml/xml'
import 'codemirror/mode/vue/vue'
import 'codemirror/mode/dockerfile/dockerfile'
import 'codemirror/keymap/vim'
import 'codemirror/mode/sql/sql'
import 'codemirror/mode/twig/twig'
import 'codemirror/mode/htmlmixed/htmlmixed'

import 'floating-vue/dist/style.css'

window.Vue = require('vue')
window.createNovaApp = config => new Nova(config)

CodeMirror.defineMode('htmltwig', function (config, parserConfig) {
  return CodeMirror.overlayMode(
    CodeMirror.getMode(config, parserConfig.backdrop || 'text/html'),
    CodeMirror.getMode(config, 'twig')
  )
})
