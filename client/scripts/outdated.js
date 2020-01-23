
function detectVersion () {
    if (navigator.appVersion.indexOf("Edge") != -1) {
        return "edge";
    } else if (navigator.appVersion.indexOf("Trident") != -1 && navigator.appVersion.indexOf("rv:11.0") != -1) {
        return "outdated";
    } else if (navigator.appVersion.indexOf("MSIE 10.0") != -1) {
        return "outdated";
    } else if (navigator.appVersion.indexOf("MSIE 9.0") != -1) {
        return "outdated";
    } else if (navigator.appVersion.indexOf("MSIE 8.0") != -1) {
        return "outdated";
    } else if (navigator.appVersion.indexOf("MSIE 7.0") != -1) {
        return "outdated"; 
    } else if (navigator.appVersion.indexOf("MSIE") != -1) {
        return "outdated";
    } else if (navigator.appVersion.indexOf("Firefox") != -1) {
        return "firefox";
    } else if (navigator.appVersion.indexOf("Chrome") != -1) {
        return "chrome";
    } else if (navigator.appVersion.indexOf("Safari") != -1) {
        return "safari";
    } else if (navigator.appVersion.indexOf("Gecko") != -1) {
        return "gecko"
    } else {
        return "outdated";
    }
}

if (detectVersion() == "outdated") {
    window.location = "http://outdatedbrowser.com/en";
}