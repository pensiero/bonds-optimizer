$(function() {

    // floating table head
    $('#content > table').floatThead();

    // graphup
    $('#content > table td.graph1').graphup({
        colorMap: 'burn'
    });
    $('#content > table td.graph2').graphup({
        colorMap: 'greenPower'
    });

});