$(document).ready(function () {
    let datatable = $('#households');
    datatable.DataTable({
        "paging": false,
        "lengthChange": false,
        "searching": false,
        language: {
            url: datatable.data('i18nUrl')
        },
        "ordering": false,
        "info": false,
        "autoWidth": false,
        "responsive": true,
    });
});
