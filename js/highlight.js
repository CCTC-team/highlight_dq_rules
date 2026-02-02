// Highlight DQ Rules Module - Client-side functionality

var arrMatches = {};
var arrIconMatches = [];

// Match fields referenced in the DQ rule logic
function matchField(ruleId, dq) {
    var matches = dq.match(/\[(.*?)\]/g);

    if (!matches) return;

    matches.forEach(function(match) {
        // remove leading digits to ensure valid CSS selector
        var cleaned = match.substring(1, match.length - 1).replace(/^\d+/, '');
        var selector = '#' + cleaned + '-tr';

        var ele = document.querySelector(selector);
        if (ele) {
            if (arrMatches[selector]) {
                if (!arrMatches[selector].includes(ruleId)) {
                    arrMatches[selector].push(ruleId);
                }
            } else {
                arrMatches[selector] = [ruleId];
            }
        }
    });
}

// Add field to icon update list
function addSingleFieldIconUpdate(fieldName) {
    arrIconMatches.push(fieldName);
}

// Highlight fields inline with error markers
function highlightInlineErrors() {
    Object.keys(arrMatches).forEach(function(key) {
        var ele = document.querySelector(key);
        if (!ele) return;

        ele.style.borderWidth = '2px';
        ele.style.borderColor = 'rgb(255, 33, 0)';

        var errRuleIds = document.createElement('div');
        errRuleIds.setAttribute('err-data-rule-id', key.substring(1, key.length));
        errRuleIds.textContent = 'related rule ids: ' + arrMatches[key].join(', ');
        ele.insertAdjacentElement('beforebegin', errRuleIds);
    });
}

// Reset field icons to default grey balloon
function resetFieldIcons() {
    arrIconMatches.forEach(function(item) {
        var ele = document.getElementById('dc-icon-' + item);
        if (!ele) return;

        var curr = ele.src;
        // replace the green tick
        if (ele.src.endsWith('tick_circle.png')) {
            ele.src = curr.replace('tick_circle.png', 'balloon_left_bw2.gif');
        }
        // replace the red exclamation
        if (ele.src.endsWith('exclamation_red.png')) {
            ele.src = curr.replace('exclamation_red.png', 'balloon_left_bw2.gif');
        }
    });
}
