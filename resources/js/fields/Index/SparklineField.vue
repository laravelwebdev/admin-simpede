<template>
  <div v-if="hasData">
    <div
      ref="chart"
      class="ct-chart"
      :style="{ width: chartWidth, height: chartHeight }"
    />
  </div>
</template>

<script>
import { BarChart, LineChart } from 'chartist'
import 'chartist/dist/index.css'

// Default chart diameters.
const defaultHeight = 50
const defaultWidth = 100

export default {
  props: ['resourceName', 'field'],

  data: () => ({ chartist: null }),

  watch: {
    'field.data': function (newData, oldData) {
      this.renderChart()
    },
  },

  methods: {
    renderChart() {
      this.chartist.update(this.field.data)
    },
  },

  mounted() {
    const chart = this.chartStyle

    this.chartist = new chart(
      this.$refs.chart,
      { series: [this.field.data] },
      {
        height: this.chartHeight,
        width: this.chartWidth,
        showPoint: false,
        fullWidth: true,
        chartPadding: { top: 0, right: 0, bottom: 0, left: 0 },
        axisX: { showGrid: false, showLabel: false, offset: 0 },
        axisY: { showGrid: false, showLabel: false, offset: 0 },
      }
    )
  },

  computed: {
    /**
     * Determine if the field has a value other than null.
     */
    hasData() {
      return this.field.data.length > 0
    },

    /**
     * Determine the chart style.
     */
    chartStyle() {
      const validTypes = ['line', 'bar']
      let chartStyle = this.field.chartStyle.toLowerCase()

      // Line and Bar are the only valid types.
      if (!validTypes.includes(chartStyle) || chartStyle === 'line')
        return LineChart

      return BarChart
    },

    /**
     * Determine the chart height.
     */
    chartHeight() {
      return this.field.height || defaultHeight
    },

    /**
     * Determine the chart width.
     */
    chartWidth() {
      return this.field.width || defaultWidth
    },
  },
}
</script>
