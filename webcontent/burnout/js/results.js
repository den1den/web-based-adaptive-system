/**
 * Created by Dennis on 8-4-2017.
 */
console.log(recoveryTxts);
console.log(rec);
var minVal = .5;
for (var i = 0; i < recoveryTxts.length; i++) {
    if (rec[i] >= minVal) {
        var recoveryTitle = recoveryTxts[i][0],
            recoveryCardContent = recoveryTxts[i][1].map(function (el) {
                return '<p>' + el + '</p>';
            }).join(''),
            card_color = ['red', 'orange', 'blue', 'green'][0];
        $("#stressors-cards").append(
            '<div class="card red darken-2">' +
            '<div class="card-content white-text">' +
            '<span class="card-title">' + recoveryTitle + '</span>' +
            recoveryCardContent
            + '</div></div>'
        );
    } else {
        console.log("Not including recovery (index=" + i + ") because value " + rec[i] + " is to low");
    }
}
console.log("burnoutScore = " + burnoutScore);
if (burnoutScore >= .75) {
    $('#burnout-result').html(
        'We estimate you have a <div class="chip result-high" id="burnout-badge">High chance</div>of having a burnout.'
    );
}
else if (burnoutScore >= .5) {
    $('#burnout-result').html(
        'We estimate you have a <div class="chip result-med" id="burnout-badge">Medium chance</div>of having a burnout.'
    );
}
if (burnoutScore >= .25) {
    $('#burnout-result').html(
        'We estimate you have a <div class="chip result-low" id="burnout-badge">Low chance</div>of having a burnout.'
    );
} else {
    $('#burnout-result').html(
        'We estimate you have <div class="chip result-none" id="burnout-badge">No chance</div>of having a burnout.'
    );
}