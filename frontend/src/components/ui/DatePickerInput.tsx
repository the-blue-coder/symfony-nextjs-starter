"use client";

import { useState } from "react";
import moment from "moment";
import { CalendarIcon } from "lucide-react";
import { Calendar } from "@/components/ui/calendar";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { cn } from "@/lib/utils";

type TDatePickerInputProps = {
	value: string;
	onChange: (value: string) => void;
	placeholder?: string;
	todayLabel?: string;
	disabled?: boolean;
	className?: string;
};

const DatePickerInput: React.FC<TDatePickerInputProps> = ({
	value,
	onChange,
	placeholder = "Pick a date",
	todayLabel = "Today",
	disabled,
	className,
}) => {
	const [open, setOpen] = useState(false);

	const selected = value ? moment(value, "YYYY-MM-DD").toDate() : undefined;

	const handleSelect = (date: Date | undefined) => {
		if (!date) return;
		onChange(moment(date).format("YYYY-MM-DD"));
		setOpen(false);
	};

	const handleToday = () => {
		onChange(moment().format("YYYY-MM-DD"));
		setOpen(false);
	};

	return (
		<Popover open={open} onOpenChange={setOpen}>
			<PopoverTrigger asChild>
				<button
					type="button"
					disabled={disabled}
					className={cn(
						"flex h-7 w-full items-center justify-center gap-1.5 rounded-lg border border-input bg-transparent px-2 text-xs transition-colors",
						"hover:bg-accent focus-visible:outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50",
						"disabled:cursor-not-allowed disabled:opacity-50",
						!value && "text-muted-foreground",
						className,
					)}
				>
					<CalendarIcon size={11} className="shrink-0 text-muted-foreground" />
					<span className="font-mono">{value ? moment(value, "YYYY-MM-DD").format("DD MMM YYYY") : placeholder}</span>
				</button>
			</PopoverTrigger>
			<PopoverContent className="w-auto p-0" align="start">
				<Calendar mode="single" selected={selected} onSelect={handleSelect} />
				<div className="border-t p-2">
					<button
						type="button"
						onClick={handleToday}
						className="w-full rounded-md px-2 py-1.5 text-xs text-center hover:bg-accent cursor-pointer transition-colors"
					>
						{todayLabel}
					</button>
				</div>
			</PopoverContent>
		</Popover>
	);
};

export default DatePickerInput;
