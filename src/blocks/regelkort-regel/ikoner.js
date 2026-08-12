export const IKON_OPTIONS = [
	{ label: 'Feber', value: 'feber', dashicon: 'thumbs-down' },
	{ label: 'Opkast', value: 'opkast', dashicon: 'warning' },
	{ label: 'Telefon', value: 'telefon', dashicon: 'phone' },
	{ label: 'Tjek', value: 'tjek', dashicon: 'yes-alt' },
	{ label: 'Info', value: 'info', dashicon: 'info' },
];

export function dashiconFor( ikon ) {
	const found = IKON_OPTIONS.find( ( o ) => o.value === ikon );
	return found ? found.dashicon : 'info';
}
