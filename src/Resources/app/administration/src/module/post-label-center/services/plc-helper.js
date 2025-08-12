const {Component, Mixin, Filter} = Shopware;
const {Criteria} = Shopware.Data;

Mixin.register('plc-helper', {
    computed: {
        dateFilter() {
            return Filter.getByName('date');
        },
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        }
    },

    methods: {
        formatDate(dateTime, onlyDate = false) {
            if(onlyDate === true){
                let date = this.dateFilter(dateTime, {
                    month: '2-digit',
                    day: '2-digit',
                    year: 'numeric',
                    hour: undefined,
                    minute: undefined,
                    second: undefined,
                })

                return date.replaceAll(".","-")
            }

            return this.dateFilter(dateTime, {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
        },

        isValidIban(input) {
            if(!input){
                return true;
            }

            let ISO_CODES = {
                AD: 24, AE: 23, AT: 20, AZ: 28, AL: 28,
                BA: 20, BE: 16, BG: 22, BH: 22, BR: 29, BY: 28,
                CH: 21, CR: 21, CY: 28, CZ: 24,
                DE: 22, DK: 18, DO: 28,
                EE: 20, ES: 24, EG: 29,
                FI: 18, FO: 18, FR: 27,
                GB: 22, GI: 23, GL: 18, GR: 27, GT: 28, GE: 22,
                HR: 21, HU: 28,
                IE: 22, IL: 23, IS: 26, IT: 27,
                JO: 30,
                KW: 30, KZ: 20,
                LB: 28, LI: 21, LT: 20, LU: 20, LV: 21, LC: 32,
                MC: 27, MD: 24, ME: 22, MK: 19, MR: 27, MT: 31, MU: 30, NL: 18, NO: 15,
                PK: 24, PL: 28, PS: 29, PT: 25,
                QA: 29,
                RO: 24, RS: 22,
                SA: 24, SE: 24, SI: 19, SK: 24, SM: 27, SC: 31, ST: 25, SV: 28,
                TN: 24, TR: 26,
                IQ: 23,
                TL: 23,
                UA: 29,
                VA: 22, VG: 24,
                XK: 20
            }

            let iban = String(input).toUpperCase().replace(/[^A-Z0-9]/g, ''), // keep only alphanumeric characters
                code = iban.match(/^([A-Z]{2})(\d{2})([A-Z\d]+)$/), // match and capture (1) the country code, (2) the check digits, and (3) the rest
                digits;

            if (!code || iban.length !== ISO_CODES[code[1]]) {
                return false;
            }

            digits = (code[3] + code[1] + code[2]).replace(/[A-Z]/g, function (letter) {
                return letter.charCodeAt(0) - 55;
            });

            return this.mod97(digits);
        },

        mod97(string) {
            let checksum = string.slice(0, 2), fragment;
            for (let offset = 2; offset < string.length; offset += 7) {
                fragment = String(checksum) + string.substring(offset, offset + 7);
                checksum = parseInt(fragment, 10) % 97;
            }
            return checksum;
        },

        jsonDecode(value, field) {
            const decodedArray = JSON.parse(value);

            if (Array.isArray(decodedArray)) {
                let list = "";

                decodedArray.forEach((el) => {
                    if (list !== "") {
                        list += ", "
                    }

                    list += el[field]
                })

                return list
            }

            return decodedArray[field]
        },
    }
})