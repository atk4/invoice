
export default {
  name: 'atk-multiline-cell',
  template: ` 
    <component :is="fieldType"
        :type="inputType"
        :fluid="true" 
        class="fluid" 
        @blur="onBlur"
        v-model="value"
        :name="fieldName" 
        ref="cell"><slot></slot></component>
  `,
  props: ['cellData', 'fieldType', 'fieldValue'],
  data() {
    return {
      field: this.cellData.field,
      //this field name will not get serialized and sent on form submit.
      fieldName: '-'+this.cellData.field,
      value: this.fieldValue,
      dirtyValue: this.fieldValue,
    }
  },
  computed: {
    inputType() {
      let type = this.cellData.type;
      switch (type) {
        case 'string':
          type = 'text';
          break;
        case 'integer':
        case 'money':
          type = 'number';
          break;
      }

      return type;
    },
    isDirty() {
      return this.dirtyValue != this.value;
    }
  },
  methods: {
    onInput: function(value) {
      //this.dirtyValue = value;
    },
    /**
     * Tell parent row that input value has changed.
     *
     * @param e
     */
    onBlur: function(e) {
      if (this.isDirty) {
        this.$emit('update-value', this.field, this.value);
        this.dirtyValue = this.value;
      }
    }
  }
}
