var xmlhttp;
var timer;

function requestUpdate() {
	xmlhttp = getXmlHttpObject();
	if (xmlhttp == null) {
		alert("Your browser does not support AJAX!");
		return;
	}
	var url = document.getElementById("requestURL").content;
	xmlhttp.onreadystatechange = stateChanged;
	xmlhttp.open("GET", url, true);
	xmlhttp.send(null);
	startTimer();
}

function stateChanged() {
	if (xmlhttp.readyState == 4) {
		if (xmlhttp.responseText.indexOf("no data") != 0) {
			document.getElementById("ajaxUpdate").innerHTML = xmlhttp.responseText;
		}
	}
}

function getXmlHttpObject() {
	if (window.XMLHttpRequest) {
		// code for IE7+, Firefox, Chrome, Opera, Safari
		return new XMLHttpRequest();
	}
	if (window.ActiveXObject) {
		// code for IE6, IE5
		return new ActiveXObject("Microsoft.XMLHTTP");
	}
	return null;
}

function startTimer() {
	timer = setTimeout("requestUpdate()", 3000);
}