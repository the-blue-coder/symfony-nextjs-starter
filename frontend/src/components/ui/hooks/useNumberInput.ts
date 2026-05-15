"use client";

import { formatAmount } from "@/lib/utils";

const useNumberInput = ({ value, onChange }: TUseNumberInputProps) => {
	const display = formatAmount(value);

	const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
		const digitsOnly = e.target.value.replace(/[^\d]/g, "");
		onChange(digitsOnly ? parseInt(digitsOnly, 10) : undefined);
	};

	return { display, handleChange };
};

type TUseNumberInputProps = {
	value: number | undefined;
	onChange: (value: number | undefined) => void;
};

export default useNumberInput;
