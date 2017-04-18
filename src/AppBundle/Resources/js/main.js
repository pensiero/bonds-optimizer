$(function() {

    // floating table head
    $('#content > table').floatThead();


    // graphup cleaner
    $.fn.graphup.cleaners.cleanTableCell = function(value, options) {
        return value.replace('.', '').replace('€', '').trim();
    };

    // graphup
    $('#content > table td.graph1').graphup({
        cleaner: 'cleanTableCell',
        decimalPoint: ',',
        colorMap: 'burn'
    });
    $('#content > table td.graph2').graphup({
        cleaner: 'cleanTableCell',
        decimalPoint: ',',
        colorMap: 'greenPower'
    });

});