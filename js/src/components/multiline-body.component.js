import multilineRow from "./multiline-row.component";

export default {
  name: "atk-multiline-body",
  template: `
    <sui-table-body>
      <atk-multiline-row v-for="(row , idx) in rows" :key="idx" :fields="fields" :rowId="getId(row)" :isDeletable="isDeletableRow(row)" :values="row"></atk-multiline-row>
    </sui-table-body>
  `,
  props: ['fieldDefs', 'rowData', 'rowIdField', 'deletables'],
  data: function() {
    return {fields: this.fieldDefs}
  },
  created: function() {
  },
  components: {
    'atk-multiline-row' : multilineRow,
  },
  computed: {
    rows() {
      return this.rowData;
    },
  },
  methods: {
    isDeletableRow(row) {
      return this.deletables.indexOf(this.getId(row)) > -1;
    },
    getId: function(row) {
      let id;
      row.forEach(input => {
        if (this.rowIdField in input) {
          id = input[this.rowIdField];
        }
      });
      return id;
    }
  },
  /**
   * Main table body rendered.
   *
   * @param h
   * @returns {*}
   */
  // render(h) {
  //   let rows = [];
  //   this.rowData.forEach((row, idx) => {
  //     console.log('render row', this.getId(row));
  //     rows.push(h('atk-multiline-row', {props: {inputs: this.fieldData, rowId: this.getId(row), isDeletable: this.isDeletableRow(row)}}));
  //   });
  //   return h('sui-table-body', {}, rows);  }
}
