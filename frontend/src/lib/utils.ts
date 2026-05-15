import { clsx, type ClassValue } from "clsx"
import { twMerge } from "tailwind-merge"

export const cn = (...inputs: ClassValue[]) => twMerge(clsx(inputs));

export const formatAmount = (value: number | undefined, locale: string = "fr-FR"): string => {
	if (value === undefined || isNaN(value)) {
		return "";
	}

	return value.toLocaleString(locale, { maximumFractionDigits: 0, minimumFractionDigits: 0 });
};
