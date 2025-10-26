var the_number_showing_thing;
var the_clicky_buttons;
var the_math_stuff = '';

function show_the_number() {
	if (the_math_stuff === '') {
		the_number_showing_thing.textContent = '0';
	} else {
		the_number_showing_thing.textContent = the_math_stuff;
	}
}

function is_it_a_math_sign(the_letter) {
	return the_letter === '+' || the_letter === '-' || the_letter === '*' || the_letter === '/' || the_letter === '÷' || the_letter === '−';
}

function add_to_math(the_thing_to_add) {
	if (the_thing_to_add === '−') {
		the_thing_to_add = '-';
	}

	if (is_it_a_math_sign(the_thing_to_add) && the_math_stuff === '') {
		if (the_thing_to_add === '-') {
			the_math_stuff = '-';
			show_the_number();
		}
		return;
	}

	if (is_it_a_math_sign(the_thing_to_add) && the_math_stuff === '-') {
		return;
	}

	if (the_math_stuff === '0' && '0123456789'.indexOf(the_thing_to_add) !== -1) {
		the_math_stuff = the_thing_to_add;
		show_the_number();
		return;
	}

	var the_last_thing = the_math_stuff.charAt(the_math_stuff.length - 1);

	if (is_it_a_math_sign(the_last_thing) && is_it_a_math_sign(the_thing_to_add)) {
		if (the_thing_to_add === '-' && the_last_thing !== '-') {
			the_math_stuff += the_thing_to_add;
		} else {
			the_math_stuff = the_math_stuff.slice(0, -1) + the_thing_to_add;
		}
		show_the_number();
		return;
	}

	if (the_thing_to_add === '.') {
		var i = the_math_stuff.length - 1;
		var last_sign_place = -1;
		while (i >= 0) {
			if (is_it_a_math_sign(the_math_stuff.charAt(i))) {
				last_sign_place = i;
				break;
			}
			i = i - 1;
		}
		var the_last_number = the_math_stuff.slice(last_sign_place + 1);
		if (the_last_number.indexOf('.') !== -1) {
			return;
		}
		if (the_last_thing === '' || is_it_a_math_sign(the_last_thing)) {
			the_math_stuff += '0.';
			show_the_number();
			return;
		}
	}

	the_math_stuff += the_thing_to_add;
	show_the_number();
}

function erase_everything() {
	the_math_stuff = '';
	show_the_number();
}

function erase_one_letter() {
	if (the_math_stuff.length > 0) {
		the_math_stuff = the_math_stuff.slice(0, -1);
	}
	show_the_number();
}

function do_the_math() {
	if (the_math_stuff.trim() === '') {
		return;
	}

	var cleaned_math = the_math_stuff;
	while (cleaned_math.indexOf('×') !== -1) {
		cleaned_math = cleaned_math.replace('×', '*');
	}
	while (cleaned_math.indexOf('÷') !== -1) {
		cleaned_math = cleaned_math.replace('÷', '/');
	}
	while (cleaned_math.indexOf('−') !== -1) {
		cleaned_math = cleaned_math.replace('−', '-');
	}

	var allowed_stuff = '0123456789+-*/.() ';
	for (var i = 0; i < cleaned_math.length; i++) {
		var the_letter = cleaned_math.charAt(i);
		if (allowed_stuff.indexOf(the_letter) === -1) {
			the_number_showing_thing.textContent = 'Error';
			the_math_stuff = '';
			return;
		}
	}

	try {
		var the_answer = eval(cleaned_math);
		the_math_stuff = String(the_answer);
		show_the_number();
	} catch (e) {
		the_number_showing_thing.textContent = 'Error';
		the_math_stuff = '';
	}
}

function start_the_calculator() {
	the_number_showing_thing = document.querySelector('.screen');
	the_clicky_buttons = document.getElementsByClassName('calcu_buttons');

	for (var i = 0; i < the_clicky_buttons.length; i++) {
		(function(the_button) {
			the_button.addEventListener('click', function() {
				var the_button_word = the_button.textContent.trim();

				if (the_button_word === 'C') {
					erase_everything();
					return;
				}
				if (the_button_word === '←' || the_button_word === '&larr;') {
					erase_one_letter();
					return;
				}
				if (the_button_word === '=') {
					do_the_math();
					return;
				}
				if (the_button_word === '×' || the_button_word === 'x') {
					add_to_math('*');
					return;
				}
				if (the_button_word === '÷') {
					add_to_math('÷');
					return;
				}

				add_to_math(the_button_word);
			});
		})(the_clicky_buttons[i]);
	}

	window.addEventListener('keydown', function(e) {
		var the_key_pressed = e.key;
		if (the_key_pressed === 'Enter' || the_key_pressed === '=') {
			e.preventDefault();
			do_the_math();
			return;
		}
		if (the_key_pressed === 'Backspace') {
			e.preventDefault();
			erase_one_letter();
			return;
		}
		if (the_key_pressed === 'Escape') {
			e.preventDefault();
			erase_everything();
			return;
		}
		if ('0123456789+-*/.'.indexOf(the_key_pressed) !== -1) {
			add_to_math(the_key_pressed);
			return;
		}
		if (the_key_pressed === 'x' || the_key_pressed === 'X') {
			add_to_math('*');
			return;
		}
	});

	show_the_number();
}

window.onload = start_the_calculator;
