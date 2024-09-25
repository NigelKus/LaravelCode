import './bootstrap';
import 'selectize';
import 'selectize/dist/css/selectize.default.css'; // Import Selectize CSS

$(document).ready(function() {
    $('#salesorder_id').selectize({
        placeholder: 'Select a Sales Order',
        sortField: 'text'
    });
});
