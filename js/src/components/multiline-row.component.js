import multilineCell from './multiline-cell.component'

/**
 * A row component.
 * This will create a table td element using sui-table-cell.
 * The td element is created only if column as set isVisible = true;
 * The td element will add a multiline cell element.
 *  the multiline cell will set it's own template component depending on the fieldType.
 *  getValue
 *
 */
export default {
  name: 'atk-multiline-row',
  template: `
    <sui-table-row :verticalAlign="'middle'">
        <sui-table-cell><input type="checkbox" @input="onToggleDelete" v-model="toDelete"></input></sui-table-cell>
        <sui-table-cell v-for="(column, idx) in columns" :key="idx" :style="{overflow: 'visible'}" v-if="column.isVisible" :textAlign="getTextAlign(column)">
         <atk-multiline-cell :fieldType="getFieldType(column)" :cellData="column" @update-value="onUpdateValue" :fieldValue="getValue(column)">{{getReadOnlyValue(column)}}</atk-multiline-cell>
        </sui-table-cell>
    </sui-table-row>
  `,
  props : ['fields', 'rowId', 'isDeletable', 'values'],
  data() {
    return { columns: this.fields}
  },
  components: {
    'atk-multiline-cell': multilineCell
  },
  inject: ['getRootData'],
  computed: {
    /**
     * toDelete is bind by v-model, thus we need a setter for
     * computed property to work.
     * When isDeletable is pass, will set checkbox according to it's value.
     */
    toDelete: {
      get: function() {
        return this.isDeletable;
      },
      set: function(v) {
        return v;
      }
    },
  },
  methods: {
    onEdit: function () {
      this.isEditing = true;
    },
    onToggleDelete(e) {
      this.$root.$emit('toggle-delete', this.rowId);
    },
    onUpdateValue: function (field, value) {
      this.$root.$emit('update-row', this.rowId, field, value);
    },
    getReadOnlyValue(column) {
      if (!column.isEditable) {
        return this.getValue(column);
      }
      return null;
    },
    getValue: function(column) {
      let temp = column.default;
      this.values.forEach(field => {
        if (column.field in field) {
          if (field[column.field]) {
            temp = field[column.field];
          }
        }
      });
      return temp;
    },
    /**
     * Setup component according to field type.
     * For now this just support regular input.
     *
     * @param column
     * @returns {string}
     */
    getFieldType: function (column) {
      let type = 'sui-input';
      if (!column.isEditable){
        type = 'div';
      }
      return type;
    },
    getTextAlign(column) {
      let align;
      switch(column.type) {
        case 'money':
        case 'integer':
        case 'number':
          align = 'right';
          break;
        default:
          align = 'left';
          break;
      }
      return align;
    },
    onDelete: function () {
    },
    onConfirm: function () {
    },
    onCancel: function () {
    },
    getColumn: function (idx) {
    },
    getError: function (idx) {
    },
    deleteRow: async function () {

    },
    saveRow: async function () {
    },
    /**
     * Get data from temporary Columns object.
     */
    prepareData: function () {
    }
  }
}
