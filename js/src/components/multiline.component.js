import multilineBody from './multiline-body.component';
import multilineHeader from './multiline-header.component';

export default {
  name: 'atk-multiline',
  template: `<div >
                <sui-table celled size="small">
                  <atk-multiline-header :fields="fieldData" :state="getMainToggleState"></atk-multiline-header>
                  <atk-multiline-body :fieldDefs="fieldData" :rowData="rowData" :rowIdField="idField" :deletables="getDeletables"></atk-multiline-body>
                  <sui-table-footer>
                    <sui-table-row>
                        <sui-table-header-cell/>
                        <sui-table-header-cell :colspan="getSpan" textAlign="right">
                        <div is="sui-button-group">
                         <sui-button size="small" @click.stop.prevent="onAdd" icon="plus"></sui-button>
                         <sui-button size="small" @click.stop.prevent="onDelete" icon="trash" :disabled="isDeleteDisable"></sui-button>                        
                         </div>
                        </sui-table-header-cell>
                    </sui-table-row>
                  </sui-table-footer>
                </sui-table>
             </div>`,
  props: {
    data: Object
  },
  data() {
    return {
      linesField: this.data.linesField, //form field where to set multiline content value.
      rows: this.getInitData(),
      fieldData: this.data.fields,
      idField: this.data.idField,
      deletables: []
    }
  },
  components: {
    'atk-multiline-body': multilineBody,
    'atk-multiline-header' : multilineHeader
  },
  created: function() {
    //this.rowData = this.getInitData();
    this.$root.$on('update-row', (id, field, value) => {
      this.updateRow(id, field, value);
    });
    this.$root.$on('toggle-delete', (id) => {
      const idx = this.deletables.indexOf(id);
      if (idx > -1) {
        this.deletables.splice(idx, 1);
      } else {
        this.deletables.push(id);
      }
    });
    this.$root.$on('toggle-delete-all', (isOn) => {
      this.deletables = [];
      if(isOn) {
        this.rowData.forEach( row => {
          this.deletables.push(this.getId(row));
        });
      }
    });
  },
  methods: {
    /**
     * UUID v4 generator.
     *
     * @returns {string}
     */
    getUUID: function() {
      return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        let r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
      });
    },
    onAdd: function(){
      this.rowData.push(this.newDataRow());
      this.updateLinesField();
    },
    onDelete: function() {
      this.deletables.forEach( id => {
        this.deleteRow(id);
      });
      this.deletables = [];
    },
    deleteRow: function(id){
      //find proper row index using id.
      //let rows = [...this.rowData];
      let rows = JSON.parse(JSON.stringify(this.rowData));

      const idx = this.findRowIndex(id);
      if (idx > -1) {
        rows.splice(idx,1);
      }
      //this.rowData = [...rows];
      //this.rowData = null;
      this.rowData = JSON.parse(JSON.stringify(rows));
      this.updateLinesField();
      // this.$nextTick(() => {
      // });
    },
    findRowIndex: function(id){
      for(let i=0; i < this.rowData.length; i++) {
        if(this.getId(this.rowData[i]) === id) {
          return i;
        }
      }
      return -1;
    },
    /**
     * Update row with proper data value.
     *
     * @param id
     * @param field
     * @param value
     */
    updateRow: async function(id, field, value) {
      // find proper row index using id.
      let idx = -1;
      for(let i = 0; i < this.rowData.length; i++) {
        this.rowData[i].forEach( cell => {
          if (cell[this.idField] === id) {
            idx = i;
            return;
          }
        })
      }
      this.updateFieldInRow(idx, field, value);
      // update proper field using row index.
      // this.rowData[idx].forEach(cell => {
      //   if (field in cell) {
      //     cell[field] = value;
      //   }
      // });

      //verify row
      let resp = await this.verifyData([...this.rowData[idx]]);
      if (resp.expressions) {
        //console.log(resp.expressions);
        let fields = Object.keys(resp.expressions);
        fields.forEach(field => {
          this.updateFieldInRow(idx, field, resp.expressions[field]);
        });
      }
      this.updateLinesField();
    },
    updateFieldInRow(idx, field, value) {
      this.rowData[idx].forEach(cell => {
        if (field in cell) {
          cell[field] = value;
        }
      });
    },
    updateLinesField: function() {
      const field = document.getElementsByName(this.linesField)[0];
      // let data = [];
      // this.rowData.forEach(row => {
      //   let cell = [];
      //   row.forEach( (cell, idx) => {
      //     if (this.fieldData[idx].id
      //   });
      // });
      field.value = JSON.stringify(this.rowData);
    },
    getInitData: function() {
      let rows = [], value = '';
      // check if input containing data is set and initialized.
      let field = document.getElementsByName(this.linesField)[0];
      if (field) {
        value = field.value;
      }
      return rows;
    },
    newDataRow: function() {
      let columns = [];
      this.data.fields.forEach(item => {
        if (item.field === this.data.idField) {
          item.default = this.getUUID();
        }
        columns.push({[item.field] : item.default});
      });
      return columns;
    },
    getId: function(row) {
      let id;
      row.forEach(input => {
        if (this.data.idField in input) {
          id = input[this.data.idField];
        }
      });
      return id;
    },
    verifyData: async function(row) {
      let data = {};
      let fields = this.fieldData.map( field => field.field);
      fields.forEach( field => {
        data[field] = row.filter(item => field in item)[0][field];
      });
      //console.log(data);
      try {
        let response = await atk.apiService.suiFetch(this.data.url, {data: data, method: 'post'});
        return response;
      } catch (e) {
        console.error(e);
      }
    },
  },
  computed: {
    rowData: {
      get: function(){
        return this.rows;
      },
      set: function(rows) {
        this.rows = [...rows];
      }
    },
    getSpan: function(){
      return this.fieldData.length;
    },
    /**
     * Get id's of row set for deletion.
     * @returns {Array}
     */
    getDeletables: function() {
      return this.deletables;
    },
    /**
     * Return Delete all checkbox state base on
     * deletables entries.
     *
     * @returns {string}
     */
    getMainToggleState() {
      let state = 'off';
      if (this.deletables.length > 0) {
        if (this.deletables.length === this.rowData.length) {
          state = 'on';
        } else {
          state = 'indeterminate';
        }
      }
      return state;
    },
    /**
     * Set delete button disabled property.
     *
     * @returns {boolean}
     */
    isDeleteDisable() {
      return !this.deletables.length > 0;
    }
  }
}
