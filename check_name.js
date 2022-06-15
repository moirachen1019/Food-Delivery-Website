
function check_name(sname)
{ 
    if (sname != ""){
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            var message;
            if (this.readyState == 4 && this.status == 200) {
                switch(this.responseText) {
                case 'YES':
                message='此店名可被使用';
                break;
                case 'NO':
                message='此店名已被註冊';
                break;
                }
                document.getElementById("msg").innerHTML = message;
                }
        };
        xhttp.open("POST", "check_name.php", true);
        xhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        xhttp.send("sname="+sname);
    }
    else
    document.getElementById("msg").innerHTML = "";
}