// callback takes one argument
// http://stackoverflow.com/questions/8567114/how-to-make-an-ajax-call-without-jquery
// data = null or undefined para usar GET
AJAX = function(url, data, callbackOK, callbackFAIL, timeout) {
	if (callbackFAIL === undefined) callbackFAIL = function(x){};
	if (callbackOK === undefined) callbackOK = function(x){};
	if (isNaN(timeout)) timeout = 30000;
	var isPost = data !== undefined && data !== null;
	var x = new XMLHttpRequest();
	if (isPost) x.open('POST', url, true);
	else        x.open('GET',  url, true);
	x.timeout = 30000;
	x.onreadystatechange = x.ontimeout = function () {
		if (x.readyState == XMLHttpRequest.DONE) {
			if (x.status == 200) {
				callbackOK(x);
			} else {
				callbackFAIL(x);
			}
		}
	};
	
	if (isPost) {
		if (typeof data === "string") {
			x.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		} else if (data.toString() === "[object FormData]") {
			// do nothing, data is already ok
		} else {
			// Data is a form
			// http://stackoverflow.com/questions/2198470/javascript-uploading-a-file-without-a-file
			// data is an array of: isFile (boolean), name (string), filename (string), mimetype (string), data (variable to send / binary blob)
			var boundary = "---------------------------36861392015894";
			body = "";
			
			for (var i = 0; i < data.length; i++) {
				body += '--' + boundary + '\r\n';
				if (data[i].isFile !== undefined && data[i].isFile === true) {
					body += 'Content-Disposition: form-data; name="files[]"; filename="' + encodeURIComponent(data[i].filename) + '"\r\n'
						  + 'Content-type: ' + data[i].mimetype;
				} else {
					body += 'Content-Disposition: form-data; name="' + data[i].name + '"';
				}
				body += '\r\n\r\n' + data[i].data + '\r\n';
			}
			
			body += '--' + boundary + '--';
			
			data = body;
		
			x.setRequestHeader("Content-Type", "multipart/form-data; boundary=" + boundary);
		}
		
		x.send(data);
	} else x.send();
};
