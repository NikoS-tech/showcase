import {Subscription, Observable, TeardownLogic, UnaryFunction} from 'rxjs';
import {Abstract} from '@decorators/abstract.decorator';

@Abstract()
export abstract class UComponent {

    protected subscriptions: Subscription = new Subscription();

    protected unsubscribe<T = any>(): UnaryFunction<Observable<T>, Observable<T>> {
        return (stream): Observable<T> => {
            const observable = new Observable<T>();
            observable.source = stream;
            observable.operator = {
                call: (subscriber: any, source: any): TeardownLogic => {
                    const subscriber$ = source && source.subscribe(subscriber);
                    this.subscriptions.add(subscriber$);
                    return subscriber$;
                }
            };

            return observable;
        }
    }

    ngOnDestroy(): void {
        this.subscriptions.unsubscribe();
    }
}
