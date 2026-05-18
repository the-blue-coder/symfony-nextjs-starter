"use client";

"use client";

import useDeleteModal from "./hooks/useDeleteModal";
import LoadingSpinner from "./LoadingSpinner";
import { Button } from "./button";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "./dialog";

const DeleteModal: React.FC<TDeleteModalProps> = ({ error, isDeleting, isOpen, message, title, onClose, onConfirm }) => {
	const { cancelLabel, confirmLabel } = useDeleteModal();

	return (
		<Dialog open={isOpen} onOpenChange={onClose}>
			<DialogContent>
				<DialogHeader>
					<DialogTitle>{title}</DialogTitle>
				</DialogHeader>
				<p className="text-sm text-muted-foreground">{message}</p>
				{error && <p className="text-sm text-destructive">{error}</p>}
				<DialogFooter>
					<Button variant="outline" onClick={onClose} disabled={isDeleting} className="cursor-pointer">
						{cancelLabel}
					</Button>
					<Button variant="destructive" onClick={onConfirm} disabled={isDeleting} className="cursor-pointer">
						{isDeleting ? <LoadingSpinner size="sm" /> : confirmLabel}
					</Button>
				</DialogFooter>
			</DialogContent>
		</Dialog>
	);
};

type TDeleteModalProps = {
	error: string | null;
	isDeleting: boolean;
	isOpen: boolean;
	message: string;
	title: string;
	onClose: () => void;
	onConfirm: () => void;
};

export default DeleteModal;
