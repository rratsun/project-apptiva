{{#unless attributes}}
    {{translate 'No attributes available for Mass Update'}}
{{else}}
<div class="button-container">
    <button class="btn btn-default pull-right hidden" data-action="reset">{{translate 'Reset'}}</button>
    <div class="btn-group">
        <button class="btn btn-default dropdown-toggle select-field" data-toggle="dropdown" tabindex="-1">{{translate 'Select Attribute'}} <span class="caret"></span></button>
        <div class="cell pull-left" style="margin-left: 40px;">
            <label><input type="checkbox" data-action="insert-mode"> {{translate 'Insert mode' category='labels' scope='Product'}}</label>
        </div>
        <ul class="dropdown-menu pull-left filter-list">
        {{#each ../attributes}}
            <li data-type="{{type}}" data-attribute-id="{{attributeId}}" data-name="{{name}}">
                <a href="javascript:" data-type="{{type}}" data-attribute-id="{{attributeId}}" data-name="{{name}}" data-action="add-attribute">{{name}}</a>
            </li>
        {{/each}}
        </ul>
    </div>
</div>
{{/unless}}
<div class="row">
    <div class="fields-container"></div>
</div>