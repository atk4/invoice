

export default {
  name: 'atk-multiline-header',
  template: `
     <sui-table-header>
        <sui-table-row :verticalAlign="'top'">
        <sui-table-header-cell collapsing><input type="checkbox" @input="onToggleDeleteAll" :checked.prop="isChecked" :indeterminate.prop="isIndeterminate" ref="check"></input></sui-table-header-cell>
        <sui-table-header-cell v-for="(column, idx) in columns" :key="idx" v-if="column.isVisible" :textAlign="getTextAlign(column)">
         {{column.caption}}
        </sui-table-header-cell>
      </sui-table-row>
    </sui-table-header>
  `,
  props: ['fields', 'state'],
  data() {
    return {columns: this.fields, isDeleteAll: false}
  },
  methods: {
    onToggleDeleteAll: function() {
      this.$nextTick(() => {
        this.$root.$emit('toggle-delete-all', this.$refs['check'].checked);
      });
    },
    getTextAlign(column) {
      let align = 'left';
      if (!column.isEditable) {
        switch(column.type) {
          case 'money':
          case 'integer':
          case 'number':
            align = 'right';
            break;
        }
      }

      return align;
    },
  },
  computed: {
    isIndeterminate() {
      return this.state === 'indeterminate';
    },
    isChecked() {
      return this.state === 'on';
    }
  }
}
