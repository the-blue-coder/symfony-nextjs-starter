"use client";

import useNumberInput from "./hooks/useNumberInput";
import { Input } from "./input";

const NumberInput: React.FC<TNumberInputProps> = ({ disabled, placeholder, value, onChange }) => {
	const { display, handleChange } = useNumberInput({ value, onChange });

	return <Input type="text" inputMode="numeric" value={display} placeholder={placeholder} disabled={disabled} onChange={handleChange} />;
};

type TNumberInputProps = {
	disabled?: boolean;
	placeholder?: string;
	value: number | undefined;
	onChange: (value: number | undefined) => void;
};

export default NumberInput;
