import {AbstractControl, UntypedFormControl, UntypedFormGroup, ValidatorFn} from '@angular/forms';
import {isEqualObjects, keys} from 'app/core/functions/functions';
import {pipeFromArray} from 'rxjs/internal/util/pipe';
import {IValidationMessages} from '@sharedModule/interfaces/main.interface';
import {BehaviorSubject, Observable, throwError, UnaryFunction} from 'rxjs';
import {UComponent} from './u.component';
import {FormElement, FormValue, List} from '@sharedModule/types/types';
import {first} from 'rxjs/operators';
import {IFormControls} from '@sharedModule/interfaces/form-controls.interface';
import {ChangeDetectorRef, inject} from '@angular/core';

export abstract class FormComponent extends UComponent {

    public form: UntypedFormGroup;
    public validationFormMessages: IValidationMessages = {};
    public formSubmitted = false;

    protected controls: IFormControls;
    protected invalidControlKey: string = null;
    protected formCache: List = null;
    protected validators: ValidatorFn[] = null;
    public isChanged: boolean;
    public emojiError: BehaviorSubject<string> = new BehaviorSubject<string>('');
    protected cdr: ChangeDetectorRef = inject(ChangeDetectorRef)

    protected formSetup(...args: any): void {};

    protected getControls(): IFormControls {return {}};

    protected setSubscriptions(): void {};

    protected formReset(data: FormValue = null): void {
        if (this.form && data) {
            return this.formPatch(data);
        }

        this.controls = this.getControls();
        this.form = new UntypedFormGroup(this.controls, this.validators);
        this.setSubscriptions();

        if (data) {
            this.formReset(data);
        }
    }

    protected formPatch(data: FormValue, options: List = {}): void {
        const value = {};
        keys(this.controls).forEach((key: string) => {
            value[key] = data && data.hasOwnProperty(key) ? data[key] : this.controls[key].value;
        });

        this.form.patchValue(value, options);
    }

    public setFormCache(): void {
        this.formCache = this.form.value;
    }

    public hasCachedChanges(): boolean {
        return !this.hasNoCachedChanges();
    }

    public hasNoCachedChanges(): boolean {
        if (!this.formCache) {
            return true;
        }

        return isEqualObjects(this.formCache, this.form.value);
    }

    protected compareFormCache(value: List): boolean {
        return isEqualObjects(this.formCache, value);
    }

    protected subscribe(control: AbstractControl, fn: (...args: any) => void, pipes: UnaryFunction<Observable<any>, any>[] = []): void {
        const subscription = control.valueChanges.pipe(pipeFromArray(pipes)).subscribe(fn);
        this.subscriptions.add(subscription);
    }

    public getControl(key: string): UntypedFormControl {
        return this.form.get(key) as UntypedFormControl;
    }

    public getDefaultValue(): FormValue {
        const controls = this.getControls();
        return new UntypedFormGroup(controls).value;
    }

    public getControlElement(name: string): FormElement {
        const control = this.form.get(name);
        if (control) {
            return control as FormElement;
        }

        throwError('Field ' + name + ' is undefined on this form');
    }

    public getControlElementValue(name: string): any {
        if (!this.form.controls[name]) {
            throwError('Field ' + name + ' is undefined on this form');
            return;
        }

        return this.form.controls[name].value;
    }

    public setFocusOnError(controls: IFormControls = null): void {
        if (!controls) {
            controls = this.form.controls;
        }

        for (const key of Object.keys(controls)) {
            const control = controls[key];

            if (!control.invalid) {
                continue;
            }

            if (!control.errors && control instanceof UntypedFormGroup) {
                this.setFocusOnError((control as UntypedFormGroup).controls);
            } else {
                this.invalidControlKey = key;
                const invalidControl = document.querySelector('[formcontrolname="' + key + '"], [formgroupname="' + key + '"]');

                if (!invalidControl) {
                    return;
                }

                const card = (invalidControl as HTMLElement).closest('.ps-card');
                const y = ((card || invalidControl) as Element).getBoundingClientRect().top + window.pageYOffset - 10;
                window.scrollTo({top: y, behavior: 'smooth'});
            }
            break;
        }
    }

    public getFormErrorList(list: string[] = []): string {
        let message = null;
        list.some((item: string) => {
            return message = this.getFormError(item);
        });

        return message;
    }

    public getFormError(key: string, force?: boolean): string {
        const validationKeys = key.split('.');
        if (validationKeys[validationKeys.length - 1] !== this.invalidControlKey && !force) {
            return null;
        }

        let messages: IValidationMessages | string = this.validationFormMessages;
        const control = validationKeys.reduce((c: UntypedFormGroup, k: string) => {
            messages = messages && messages[k] ? messages[k] : null;
            return c.controls.hasOwnProperty(k) ? c.get(k) : c;
        }, this.form);

        if (force && control.errors) {
            const rule = Object.keys(control.errors)[0];
            return typeof messages === 'string' ? messages : (messages && messages[rule] ? messages[rule] : 'Unexpected error') as string;
        }

        if (control.errors && (control.touched || this.formSubmitted)) {
            const rule = Object.keys(control.errors)[0];
            return typeof messages === 'string' ? messages : (messages && messages[rule] ? messages[rule] : 'Unexpected error') as string;
        }

        return '';
    }

    protected checkToChangeForm(pipes: UnaryFunction<Observable<any>, any>[] = []): void {
        const defaultPipes = [first(), this.unsubscribe()];
        this.form.valueChanges.pipe(pipeFromArray([...(pipes || []), ...defaultPipes])).subscribe(() => {
            this.isChanged = true;
            this.cdr.detectChanges();
        });
    }

    protected emojiValidatorListener(control: AbstractControl<string>, message?: string): void {
        control.statusChanges.pipe(this.unsubscribe()).subscribe(status => {
            if (status === 'VALID') {
                this.emojiError.next('');
                return;
            }

            this.emojiError.next(message || control.getError('emojiError'));
        });
    }
}
