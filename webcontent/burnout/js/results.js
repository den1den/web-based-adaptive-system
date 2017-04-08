/**
 * Created by Dennis on 8-4-2017.
 */
console.log(recoveryTxts);
console.log(rec);
var minVal = .5;
for(var i = 0; i < recoveryTxts.length; i++){
    if(rec[i] >= minVal){
        var recoveryTitle = recoveryTxts[i][0],
            recoveryCardContent = recoveryTxts[i][1].map(function (el) {
                return '<p>' + el + '</p>';
            }).join(''),
            card_color = ['red', 'orange', 'blue', 'green'][0];
        $("#stressors-cards").html(
            '<div class="card red darken-2">' +
            '<div class="card-content white-text">' +
            '<span class="card-title">' + recoveryTitle + '</span>' +
            recoveryCardContent
            + '</div></div>'
        );
    } else {
        console.log("Not including recovery (index="+i+") because value "+rec[i]+" is to low");
    }
}