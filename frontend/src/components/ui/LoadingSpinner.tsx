const LoadingSpinner: React.FC<TLoadingSpinnerProps> = ({ size = "md" }) => {
	return <div className={`animate-spin rounded-full border-2 border-current border-t-transparent ${sizeClasses[size]}`} />;
};

const sizeClasses: Record<NonNullable<TLoadingSpinnerProps["size"]>, string> = {
	lg: "h-8 w-8",
	md: "h-6 w-6",
	sm: "h-4 w-4",
};

type TLoadingSpinnerProps = {
	size?: "sm" | "md" | "lg";
};

export default LoadingSpinner;
