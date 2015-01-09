
/**
 * clone DIV-Content to generate multiple DB-Definitions in Step 3
 */
var DivCounter = 0;
function cloneFields()
{
	var tpl = $('#DBdefinition').html();
	$('#writeout').append( tpl.replace(/\[\[x\]\]/g, DivCounter).replace(/\[x\]/g, '['+DivCounter+']') );
	DivCounter++;
}

/**
 * show/hide some Fields in Step 3
 * @param el
 * @param i
 */
function toggleFields(el, i)
{
	var d = [false,false,false,false,false,false,false,false,false,false,false,false,false,false];
	if(el.value=='mysql') d = [true,true,false,true,false,true,true,true,true,true,true,true,false,false];// show if MySql
	if(el.value=='sqlite') d = [true,true,true,true,true,false,false,false,false,false,false,false,false,false];// show if SQLite

	// toggle
	$('#divDbalias'+i).toggle(d[0]);

	$('#divDbname'+i).toggle(d[1]);
	$('#putDbname'+i).toggle(d[2]);//random db name

	$('#divDbpass'+i).toggle(d[3]);
	$('#putDbpass'+i).toggle(d[4]);//randon db password

	$('#divDbhost'+i).toggle(d[5]);
	$('#putDbhost'+i).toggle(d[6]);

	$('#divDbport'+i).toggle(d[7]);
	$('#putDbport'+i).toggle(d[8]);

	$('#divDbuser'+i).toggle(d[9]);

	$('#divDbrootname'+i).toggle(d[10]);
	$('#divDbrootpass'+i).toggle(d[11]);

}

// put a random string into some fields
// (string-length, input-id, mime-addition)
function randomString(len, id, add)
{
	var chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz'.split('');

	if (!len) {
		len = Math.floor(Math.random() * chars.length);
	}
	var str = '';
	for (var i = 0; i < len; i++) {
		str += chars[Math.floor(Math.random() * chars.length)];
	}
	$('#'+id).val(str + add);
}

// remove forbidden characters from project-name
function clearString(el) {
	el.value = el.value.replace(' ','_').replace(/[^\d\w]/g, '').toLowerCase();
}

$(document).ready(function()
{
	// hide label-elements if placeholders are avalable
	if('placeholder' in document.createElement('input')) {
		$('label').hide();
	}
});
