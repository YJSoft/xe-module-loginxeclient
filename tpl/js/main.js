/**
 * Created by YJSoft on 2015-04-08.
 */
function chkServerVersion(data)
{
    if (data.version == '' || data.version == '1.0') {
        jQuery('#version_info').html('프로토콜 버전 <span style="color:red">1.0</span></p><p><b>업데이트가 필요합니다.</b>');
    }
    else jQuery('#version_info').html('프로토콜 버전 <span style="color:blue">' + data.version + '</span></p><p>최신 버전입니다.');
}