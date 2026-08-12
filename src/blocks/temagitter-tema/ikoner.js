export const IKON_OPTIONS = [
	{ label: 'Personlig udvikling', value: 'udvikling', dashicon: 'star-filled' },
	{ label: 'Sociale kompetencer', value: 'sociale', dashicon: 'admin-users' },
	{ label: 'Sprog', value: 'sprog', dashicon: 'testimonial' },
	{ label: 'Krop og bevægelse', value: 'krop', dashicon: 'universal-access-alt' },
	{ label: 'Natur', value: 'natur', dashicon: 'palmtree' },
	{ label: 'Kultur', value: 'kultur', dashicon: 'art' },
];

export function dashiconFor( ikon ) {
	const found = IKON_OPTIONS.find( ( o ) => o.value === ikon );
	return found ? found.dashicon : 'star-filled';
}
