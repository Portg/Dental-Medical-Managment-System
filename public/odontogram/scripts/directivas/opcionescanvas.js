app.directive('opcionescanvas', function () {
    function t(key, fallback) {
        if (typeof LanguageManager !== 'undefined' && LanguageManager.trans) {
            var v = LanguageManager.trans('odontogram.' + key);
            if (v && v !== 'odontogram.' + key) {
                return v;
            }
        }
        return fallback;
    }

    return {
        restrict: 'E',
        scope: {},
        template: function () {
            return '<br><input type="button" class="hidden" value="ver" id="ver"/>\n' +
                '<input type="button" value="' + t('save_changes', '保存更改') + '" class="btn green-meadow" id="add"/>\n' +
                '<input type="button" class="hidden" value="clean" id="clean"/>\n' +
                '\n' +
                '<input type="radio" class="hidden" id="Decidua" name="kind" value="1" checked/>' +
                '<input type="radio" class="hidden" id="Children" name="kind" value="2"/>' +
                '<input type="radio" class="hidden" id="Mixed" name="kind" value="3"/> <br><br>' +
                '<table border="1" align="center" width="600px">\n' +
                '    <tr>\n' +
                '        <th> ' + t('filling', '补牙') + ' </th>' +
                '        <th> ' + t('caries', '龋齿') + ' </th>' +
                '        <th> ' + t('endodontics', '根管治疗') + ' </th>' +
                '        <th> ' + t('extraction', '拔牙') + ' </th>' +
                '        <th class="hidden">' + t('resin', '树脂') + '</th>' +
                '        <th> ' + t('implant', '种植体') + ' </th>\n' +
                '        <th class="hidden">' + t('sealant', '窝沟封闭') + '</th>' +
                '        <th> ' + t('crown', '牙冠') + ' </th>' +
                '        <th> ' + t('impacted_teeth', '阻生牙') + ' </th>' +
                '        <th class="text-danger"> ' + t('unmark_tooth', '取消标记') + ' </th>' +
                '    </tr>\n' +
                '    <td>\n' +
                '        <center>\n' +
                '            <div class="color" value="1" style="background-color:red;width:20px;height:20px"></div>\n' +
                '        </center>\n' +
                '    </td>\n' +
                '    <td>\n' +
                '        <center>\n' +
                '            <div class="color" value="2" style="background-color:yellow;width:20px;height:20px"></div>\n' +
                '        </center>\n' +
                '    </td>\n' +
                '    <td>\n' +
                '        <center>\n' +
                '            <div class="color" value="3" style="background-color:orange;width:20px;height:20px"></div>\n' +
                '        </center>\n' +
                '    </td>\n' +
                '    <td>\n' +
                '        <center>\n' +
                '            <div class="color" value="4" style="background-color:tomato;width:20px;height:20px"></div>\n' +
                '        </center>\n' +
                '    </td>\n' +
                '    <td class="hidden">\n' +
                '        <center>\n' +
                '            <div class="color" value="5" style="background-color:#CC6600;width:20px;height:20px"></div>\n' +
                '        </center>\n' +
                '    </td>\n' +
                '    <td>\n' +
                '        <center>\n' +
                '            <div class="color" value="6" style="background-color:#CC66CC;width:20px;height:20px"></div>\n' +
                '        </center>\n' +
                '    </td>\n' +
                '    <td class="hidden">\n' +
                '        <center>\n' +
                '            <div class="color" value="7" style="background-color:green;width:20px;height:20px"></div>\n' +
                '        </center>\n' +
                '    </td>\n' +
                '    <td>\n' +
                '        <center>\n' +
                '            <div class="color" value="8" style="background-color:blue;width:20px;height:20px"></div>\n' +
                '        </center>\n' +
                '    </td>\n' +
                '    <td>\n' +
                '        <center>\n' +
                '            <div class="color" value="11" style="background-color:#29e1b1;width:20px;height:20px"></div>\n' +
                '        </center>\n' +
                '    </td>\n' +
                '    <td>\n' +
                '        <center>\n' +
                '            <div class="color" value="9" style="background-color:black;width:20px;height:20px"></div>\n' +
                '        </center>\n' +
                '    </td>\n' +
                '    <tr>\n' +
                '</table>\n';
        }
    };
});
