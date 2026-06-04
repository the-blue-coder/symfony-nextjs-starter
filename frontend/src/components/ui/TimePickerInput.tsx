"use client";

import { useEffect, useRef, useState } from "react";
import { ClockIcon } from "lucide-react";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { cn } from "@/lib/utils";

const HOURS = Array.from({ length: 24 }, (_, i) => String(i).padStart(2, "0"));
const MINUTES = Array.from({ length: 60 }, (_, i) => String(i).padStart(2, "0"));

type TTimePickerInputProps = {
	value: string;
	onChange: (value: string) => void;
	placeholder?: string;
	nowLabel?: string;
	disabled?: boolean;
	className?: string;
};

const TimePickerInput: React.FC<TTimePickerInputProps> = ({
	value,
	onChange,
	placeholder = "HH:MM",
	nowLabel = "Now",
	disabled,
	className,
}) => {
	const [open, setOpen] = useState(false);
	const hourRef = useRef<HTMLDivElement>(null);
	const minuteRef = useRef<HTMLDivElement>(null);

	const [selectedHour, selectedMinute] = value ? value.split(":") : ["", ""];

	useEffect(() => {
		if (!open) return;
		const scrollTo = (ref: React.RefObject<HTMLDivElement | null>, value: string, items: string[]) => {
			const index = items.indexOf(value);
			if (index >= 0 && ref.current) {
				ref.current.scrollTop = index * 32;
			}
		};
		setTimeout(() => {
			scrollTo(hourRef, selectedHour, HOURS);
			scrollTo(minuteRef, selectedMinute, MINUTES);
		}, 0);
	}, [open, selectedHour, selectedMinute]);

	const handleSelect = (type: "hour" | "minute", val: string) => {
		const h = type === "hour" ? val : (selectedHour || "00");
		const m = type === "minute" ? val : (selectedMinute || "00");
		onChange(`${h}:${m}`);
		if (type === "minute") setOpen(false);
	};

	const handleNow = () => {
		const now = new Date();
		const h = String(now.getHours()).padStart(2, "0");
		const m = String(now.getMinutes()).padStart(2, "0");
		onChange(`${h}:${m}`);
		setOpen(false);
	};

	return (
		<Popover open={open} onOpenChange={setOpen}>
			<PopoverTrigger asChild>
				<button
					type="button"
					disabled={disabled}
					className={cn(
						"flex h-7 w-full items-center justify-center gap-1.5 rounded-lg border border-input bg-transparent px-2 text-xs font-mono transition-colors",
						"hover:bg-accent focus-visible:outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50",
						"disabled:cursor-not-allowed disabled:opacity-50",
						!value && "text-muted-foreground",
						className,
					)}
				>
					<ClockIcon size={11} className="shrink-0 text-muted-foreground" />
					<span>{value || placeholder}</span>
				</button>
			</PopoverTrigger>
			<PopoverContent className="w-auto p-1" align="start">
				<button
					type="button"
					onClick={handleNow}
					className="w-full rounded-md px-2 py-1.5 text-xs text-center hover:bg-accent cursor-pointer transition-colors"
				>
					{nowLabel}
				</button>
				<div className="flex gap-1">
					<div ref={hourRef} className="h-48 w-12 overflow-y-auto scroll-smooth rounded border">
						{HOURS.map((h) => (
							<button
								key={h}
								type="button"
								onClick={() => handleSelect("hour", h)}
								className={cn(
									"flex h-8 w-full items-center justify-center text-xs font-mono hover:bg-accent cursor-pointer",
									h === selectedHour && "bg-primary text-primary-foreground hover:bg-primary/90",
								)}
							>
								{h}
							</button>
						))}
					</div>
					<div ref={minuteRef} className="h-48 w-12 overflow-y-auto scroll-smooth rounded border">
						{MINUTES.map((m) => (
							<button
								key={m}
								type="button"
								onClick={() => handleSelect("minute", m)}
								className={cn(
									"flex h-8 w-full items-center justify-center text-xs font-mono hover:bg-accent cursor-pointer",
									m === selectedMinute && "bg-primary text-primary-foreground hover:bg-primary/90",
								)}
							>
								{m}
							</button>
						))}
					</div>
				</div>
			</PopoverContent>
		</Popover>
	);
};

export default TimePickerInput;
