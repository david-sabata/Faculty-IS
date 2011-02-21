
// set all not-implemented links to throw an error on click
$(function(){
   $('a.not-implemented').click(function(){alert('Není součástí systému');return false;});
   $('a.not-implemented').css('cursor', 'not-allowed');
});

// set up datepicker
$(function() {
   $('input.datetimepicker').datepicker({
     duration: '',
     changeMonth: true,
     changeYear: true,
     yearRange: '2007:2020',
     showTime: true,
     time24h: true,
     currentText: 'Dnes',
     closeText: 'OK',
     dayNames: ['Neděle', 'Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota'],
     dayNamesMin: ['Ne', 'Po', 'Út', 'St', 'Čt', 'Pá', 'So'],
     monthNamesShort: ['Leden', 'Únor', 'Březen', 'Duben', 'Květen', 'Červen', 'Červenec', 'Srpen',
        'Září', 'Říjen', 'Listopad', 'Prosinec'],
     dateFormat: 'd. m. yy'
   });
});

// add a "required" star
$(function() {
   $('label.required').append(' <span class="star red">*</span>');
});

// clock in the submenu
$(function(){
   $('#submenu').append('<div style="width:200px; position:absolute; top:0; right:5px; text-align:right; color:#777" class="clock"></div>');
   if ($('#submenu div.clock').length > 0) {      
      setInterval(function(){
         var d = new Date();
         var m = d.getMinutes();
         if (m < 10) m = '0' + m;
         var s = d.getSeconds();
         if (s < 10) s = '0' + s;
         $('#submenu div.clock').html(d.getDate() + '.' + (d.getMonth()+1) + '.' + d.getFullYear() + ',  ' + d.getHours() + ':' + m + ':' + s);
      }, 1000);
   }
});



// add :regexp selector to jQuery
// author: James Padolsey, http://james.padolsey.com
jQuery.expr[':'].regex = function(elem, index, match) {
    var matchParams = match[3].split(','),
        validLabels = /^(data|css):/,
        attr = {
            method: matchParams[0].match(validLabels) ?
                        matchParams[0].split(':')[0] : 'attr',
            property: matchParams.shift().replace(validLabels,'')
        },
        regexFlags = 'ig',
        regex = new RegExp(matchParams.join('').replace(/^\s+|\s+$/g,''), regexFlags);
    return regex.test(jQuery(elem)[attr.method](attr.property));
}


// collapsible divs
$(function(){
	$('.collapsible .toggle').click(function() {
		$(this).next().toggle('slow');
		return false;
	}).next().hide();
});


// placeholders
$(function(){
   $('input[placeholder]').placeholder({ overrideSupport:true });
});
