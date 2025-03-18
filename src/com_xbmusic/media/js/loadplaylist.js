/**
 * @package xbmusic
 * @filesource /media/js/loadplaylist.js
 * @version 0.0.42.3 15th March 2025
 * @desc used by ajax call to get playlists for station
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 * 
**/

const loadplaylist = (event) => {
    // the element ids are the field names prefixed by whatever is passed as 'control' in the loadForm call in the Model, plus "_"
    let dbstid = document.getElementById("jform_azstation").value;
    let data = { dbstid: dbstid };
    let answer = document.getElementById("jform_azplaylist");
    
    // get the URL root which is passed down from the PHP code
    let vars = Joomla.getOptions('com_xbmusic.uri');
    let url = vars.root + 'administrator/index.php?option=com_xbmusic&format=json&task=ajax.getplaylistfield';

    fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body:  new URLSearchParams(data).toString()
    })
    .then(response => response.json())
    .then(result => { 
        if (result.success)
        {
            answer.value = result.data; 
            // render the passed message as an Info message in the messages area
            Joomla.renderMessages({"info": [result.message]});
        }
        else
        {
            alert(result.message);
        }
        // display the enqueued messages in the message area
        // pass 3rd param as 'true' so that the previous message doesn't get removed
        if (result.messages) {
            Joomla.renderMessages(result.messages, undefined, true);
        }
    })
    .catch( err => {
        let errName = err.name;
        let errMsg = err.message;
        alert(`Ajax failed! error name: ${errName}, message: ${errMsg}`);
    });
};
