import Nova from './nova.js'

import 'floating-vue/dist/style.css'

window.Vue = require('vue')
window.LaravelNova = require('./mixins/packages')
window.LaravelNovaUtil = require('./util')
window.createNovaApp = config => new Nova(config)
